<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AbsensiResource\Pages;
use App\Filament\Resources\AbsensiResource\RelationManagers;
use App\Models\Absensi;
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
                    ->relationship('siswa', 'nama')
                    ->searchable()
                    ->preload()
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
}