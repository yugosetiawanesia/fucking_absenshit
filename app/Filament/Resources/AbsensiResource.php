<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AbsensiResource\Pages;
use App\Filament\Resources\AbsensiResource\RelationManagers;
use App\Models\Absensi;
use App\Models\HariLibur;
use App\Models\LiburSemester;
use App\Models\Kelas;
use App\Models\Siswa;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\Setting;

class AbsensiResource extends Resource
{
    protected static ?string $model = Absensi::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Absensi';

    protected static ?string $navigationGroup = 'Absensi';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('siswa_id')
                    ->label('Siswa')
                    ->searchable()
                    ->getSearchResultsUsing(function (string $search) {
                        return \App\Models\Siswa::with('kelas')
                            ->where('nama', 'like', "%{$search}%")
                            ->orWhere('nis', 'like', "%{$search}%")
                            ->limit(50)
                            ->get()
                            ->map(function ($siswa) {
                                return [
                                    'id' => $siswa->id,
                                    'nama' => $siswa->nama . ' (' . $siswa->nis . ' / ' . $siswa->kelas->nama_kelas . ')',
                                ];
                            })
                            ->pluck('nama', 'id');
                    })
                    ->getOptionLabelUsing(function ($value) {
                        $siswa = \App\Models\Siswa::with('kelas')->find($value);
                        if ($siswa) {
                            return $siswa->nama . ' (' . $siswa->nis . ' / ' . $siswa->kelas->nama_kelas . ')';
                        }
                        return $value;
                    })
                    ->required(),
                Forms\Components\DatePicker::make('tanggal')
                    ->required(),
                Forms\Components\TimePicker::make('jam_datang'),
                Forms\Components\TimePicker::make('jam_pulang'),
                Forms\Components\Select::make('status')
                    ->options([
                        'hadir' => 'Hadir',
                        'izin' => 'Izin',
                        'sakit' => 'Sakit',
                        'alpa' => 'Alpa',
                        'libur' => 'Libur',
                    ])
                    ->required(),
                Forms\Components\Textarea::make('keterangan')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                // Get filter data from session
                $kelasId = session('absensi_filter_kelas_id');
                $status = session('absensi_filter_status');
                $tanggal = session('absensi_filter_tanggal', Carbon::now()->format('Y-m-d'));
                
                $date = \Carbon\CarbonImmutable::parse($tanggal);
                $allowSundayAttendance = Setting::getBool('absensi.allow_sunday_attendance', false);
                $workdaysPerWeek = (int) (Setting::getString('absensi.workdays_per_week', '6') ?? '6');

                // Jika setting berubah (mis. dari 5 hari ke 6 hari kerja), bersihkan data libur otomatis
                // agar tampilan tabel langsung sinkron.
                if ($date->isSaturday() && $workdaysPerWeek == 6) {
                    Absensi::query()
                        ->whereDate('tanggal', $tanggal)
                        ->where('status', 'libur')
                        ->where('keterangan', 'Hari Sabtu')
                        ->delete();
                }

                if ($date->isSunday() && $allowSundayAttendance) {
                    Absensi::query()
                        ->whereDate('tanggal', $tanggal)
                        ->where('status', 'libur')
                        ->where('keterangan', 'Hari Minggu')
                        ->delete();
                }

                // Check if the date is a holiday using the same logic as laporan
                $isHoliday = static::isHariLibur($tanggal);
                
                // If it's NOT a holiday anymore, clean up any existing libur absensi
                if (!$isHoliday) {
                    // Get all students based on kelas filter
                    $studentsQuery = \App\Models\Siswa::query();
                    if ($kelasId) {
                        $studentsQuery->where('kelas_id', $kelasId);
                    }
                    
                    $students = $studentsQuery->get();
                    
                    foreach ($students as $student) {
                        // Delete any libur absensi for this student and date
                        \App\Models\Absensi::where('siswa_id', $student->id)
                            ->whereDate('tanggal', $tanggal)
                            ->where('status', 'libur')
                            ->delete();
                    }
                }
                
                // If it's a holiday, validate and auto-create libur absensi for all students
                if ($isHoliday) {
                    // Get holiday information
                    $hariLiburInfo = static::getHariLiburInfo($tanggal);
                    $liburKeterangan = $hariLiburInfo ?? 'Hari Libur';
                    
                    // Get all students based on kelas filter
                    $studentsQuery = \App\Models\Siswa::query();
                    if ($kelasId) {
                        $studentsQuery->where('kelas_id', $kelasId);
                    }
                    
                    $students = $studentsQuery->get();
                    
                    foreach ($students as $student) {
                        // Check if absensi already exists for this student and date
                        $existingAbsensi = \App\Models\Absensi::where('siswa_id', $student->id)
                            ->whereDate('tanggal', $tanggal)
                            ->first();
                        
                        // If no absensi exists, create one with libur status
                        if (!$existingAbsensi) {
                            \App\Models\Absensi::create([
                                'siswa_id' => $student->id,
                                'tanggal' => $tanggal,
                                'status' => 'libur',
                                'keterangan' => $liburKeterangan
                            ]);
                        } else {
                            // If absensi exists, ensure it's marked as libur and keterangan is synchronized
                            $needsUpdate = $existingAbsensi->status !== 'libur'
                                || ($existingAbsensi->keterangan ?? '') !== $liburKeterangan;

                            if (! $needsUpdate) {
                                continue;
                            }

                            $existingAbsensi->update([
                                'status' => 'libur',
                                'keterangan' => $liburKeterangan
                            ]);
                        }
                    }
                }
                
                // Create a subquery to get all students with their attendance for the selected date
                $query->select(
                    'siswa.*',
                    'absensi.id as absensi_id',
                    'absensi.tanggal as absensi_tanggal',
                    'absensi.status as absensi_status',
                    'absensi.jam_datang as absensi_jam_datang',
                    'absensi.jam_pulang as absensi_jam_pulang',
                    'absensi.keterangan as absensi_keterangan',
                    'kelas.nama_kelas as kelas_nama_kelas'
                )
                ->from('siswa')
                ->leftJoin('kelas', 'siswa.kelas_id', '=', 'kelas.id')
                ->leftJoin('absensi', function ($join) use ($tanggal) {
                    $join->on('siswa.id', '=', 'absensi.siswa_id')
                         ->whereDate('absensi.tanggal', $tanggal);
                });
                
                // Apply class filter
                if ($kelasId) {
                    $query->where('siswa.kelas_id', $kelasId);
                }
                
                // Apply status filter
                if ($status) {
                    if ($status === 'alpa') {
                        // Show students without attendance record
                        $query->whereNull('absensi.id');
                    } else {
                        // Show students with specific status
                        $query->where('absensi.status', $status);
                    }
                }
            })
            ->columns([
                Tables\Columns\TextColumn::make('nis')
                    ->label('NIS')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('nama')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('kelas_nama_kelas')
                    ->label('Kelas')
                    ->sortable(),
                Tables\Columns\TextColumn::make('absensi_status')
                    ->label('Status')
                    ->getStateUsing(function ($record) {
                        return $record->absensi_status ?? 'alpa';
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'hadir' => 'success',
                        'izin' => 'info',
                        'sakit' => 'warning',
                        'alpa' => 'danger',
                        'libur' => 'gray',
                    }),
                Tables\Columns\TextColumn::make('absensi_jam_datang')
                    ->label('Jam Datang')
                    ->getStateUsing(function ($record) {
                        return $record->absensi_jam_datang ?? '-';
                    }),
                Tables\Columns\TextColumn::make('absensi_jam_pulang')
                    ->label('Jam Pulang')
                    ->getStateUsing(function ($record) {
                        return $record->absensi_jam_pulang ?? '-';
                    }),
                Tables\Columns\TextColumn::make('absensi_keterangan')
                    ->label('Keterangan')
                    ->getStateUsing(function ($record) {
                        return $record->absensi_keterangan ?? '-';
                    })
                    ->limit(50),
            ])
            ->filters([
                // No filters here - all filters are in the form
            ])
            ->actions([
                Tables\Actions\Action::make('view_absensi')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->url(function ($record) {
                        if ($record->absensi_id) {
                            return \App\Filament\Resources\AbsensiResource::getUrl('view', ['record' => $record->absensi_id]);
                        }
                        
                        // For alpa status, redirect to create page with pre-filled data
                        $selectedDate = session('absensi_filter_tanggal', Carbon::now()->format('Y-m-d'));
                        return \App\Filament\Resources\AbsensiResource::getUrl('create') . 
                               '?siswa_id=' . $record->id . 
                               '&tanggal=' . $selectedDate;
                    }),
            ])
            ->bulkActions([
                // Bulk actions can be added here if needed
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAbsensis::route('/'),
            'create' => Pages\CreateAbsensi::route('/create'),
            'view' => Pages\ViewAbsensi::route('/{record}'),
            'edit' => Pages\EditAbsensi::route('/{record}/edit'),
        ];
    }

    /**
     * Check if a date is a holiday
     */
    protected static function isHariLibur($date): bool
    {
        $date = $date instanceof \Carbon\CarbonImmutable 
            ? $date 
            : \Carbon\CarbonImmutable::parse($date);
            
        $allowSundayAttendance = Setting::getBool('absensi.allow_sunday_attendance', false);
        $workdaysPerWeek = (int) (Setting::getString('absensi.workdays_per_week', '6') ?? '6');

        // Cek apakah hari Minggu dan tidak diizinkan absen
        if ($date->isSunday() && ! $allowSundayAttendance) {
            return true;
        }
        
        // Sistem 5 hari kerja: Sabtu otomatis libur
        if ($date->isSaturday() && $workdaysPerWeek == 5) {
            return true;
        }
        
        // Cek apakah tanggal tersebut hari libur (manual dari pengaturan)
        if (HariLibur::query()
            ->whereDate('tanggal', $date->toDateString())
            ->exists()) {
            return true;
        }
        
        // Cek apakah tanggal tersebut dalam rentang libur semester
        return LiburSemester::query()
            ->where('tanggal_mulai', '<=', $date->toDateString())
            ->where('tanggal_selesai', '>=', $date->toDateString())
            ->exists();
    }

    /**
     * Get holiday information for a date
     */
    protected static function getHariLiburInfo($date): ?string
    {
        $date = $date instanceof \Carbon\CarbonImmutable 
            ? $date 
            : \Carbon\CarbonImmutable::parse($date);

        $allowSundayAttendance = Setting::getBool('absensi.allow_sunday_attendance', false);
        $workdaysPerWeek = (int) (Setting::getString('absensi.workdays_per_week', '6') ?? '6');
            
        // Cek apakah hari Minggu
        if ($date->isSunday() && ! $allowSundayAttendance) {
            return 'Hari Minggu';
        }
        
        // Cek apakah hari Sabtu
        if ($date->isSaturday() && $workdaysPerWeek == 5) {
            return 'Hari Sabtu';
        }
        
        // Cek apakah tanggal tersebut hari libur (manual dari pengaturan)
        $hariLibur = HariLibur::query()
            ->whereDate('tanggal', $date->toDateString())
            ->first();
            
        if ($hariLibur) {
            return $hariLibur->keterangan;
        }
        
        // Cek apakah tanggal tersebut dalam rentang libur semester
        $liburSemester = LiburSemester::query()
            ->where('tanggal_mulai', '<=', $date->toDateString())
            ->where('tanggal_selesai', '>=', $date->toDateString())
            ->first();
            
        if ($liburSemester) {
            return $liburSemester->nama_libur;
        }
            
        return null;
    }
}