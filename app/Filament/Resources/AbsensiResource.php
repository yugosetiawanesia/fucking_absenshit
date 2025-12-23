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
    protected static ?string $model = Siswa::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Absensi';

    protected static ?string $navigationGroup = 'Absensi';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('siswa_id')
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
                        'hadir' => 'hadir',
                        'izin' => 'izin',
                        'sakit' => 'sakit',
                        'alpa' => 'alpa',
                        'libur' => 'libur',
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
                
                // Apply class filter
                if ($kelasId) {
                    $query->where('kelas_id', $kelasId);
                }
                
                // Apply status filter
                if ($status) {
                    if ($status === 'alpa') {
                        // Show students without attendance record
                        $query->whereDoesntHave('absensi', function ($q) use ($tanggal) {
                            $q->whereDate('tanggal', $tanggal);
                        });
                    } else {
                        // Show students with specific status
                        $query->whereHas('absensi', function ($q) use ($tanggal, $status) {
                            $q->whereDate('tanggal', $tanggal)->where('status', $status);
                        });
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
                Tables\Columns\TextColumn::make('kelas.nama_kelas')
                    ->label('Kelas')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status_absensi')
                    ->label('Status')
                    ->getStateUsing(function ($record) {
                        // Get date from session or use today
                        $selectedDate = session('absensi_filter_tanggal', Carbon::now()->format('Y-m-d'));
                        
                        $absensi = $record->absensi()->whereDate('tanggal', $selectedDate)->first();
                        
                        if ($absensi) {
                            return $absensi->status;
                        }
                        
                        return 'alpa'; // Default to alpa if no attendance record
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'hadir' => 'success',
                        'izin' => 'info',
                        'sakit' => 'warning',
                        'alpa' => 'danger',
                        'libur' => 'gray',
                    }),
                Tables\Columns\TextColumn::make('jam_datang')
                    ->label('Jam Datang')
                    ->getStateUsing(function ($record) {
                        $selectedDate = session('absensi_filter_tanggal', Carbon::now()->format('Y-m-d'));
                        
                        $absensi = $record->absensi()->whereDate('tanggal', $selectedDate)->first();
                        return $absensi?->jam_datang ?? '-';
                    }),
                Tables\Columns\TextColumn::make('jam_pulang')
                    ->label('Jam Pulang')
                    ->getStateUsing(function ($record) {
                        $selectedDate = session('absensi_filter_tanggal', Carbon::now()->format('Y-m-d'));
                        
                        $absensi = $record->absensi()->whereDate('tanggal', $selectedDate)->first();
                        return $absensi?->jam_pulang ?? '-';
                    }),
                Tables\Columns\TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->getStateUsing(function ($record) {
                        $selectedDate = session('absensi_filter_tanggal', Carbon::now()->format('Y-m-d'));
                        
                        $absensi = $record->absensi()->whereDate('tanggal', $selectedDate)->first();
                        return $absensi?->keterangan ?? '-';
                    })
                    ->limit(50),
            ])
            ->filters([
                // No filters here - all filters are in the form
            ])
            ->actions([
                Tables\Actions\Action::make('edit_absensi')
                    ->label('Edit')
                    ->icon('heroicon-o-pencil')
                    ->url(function ($record) {
                        $selectedDate = session('absensi_filter_tanggal', Carbon::now()->format('Y-m-d'));
                        
                        $absensi = $record->absensi()->whereDate('tanggal', $selectedDate)->first();
                        
                        if ($absensi) {
                            return static::getUrl('edit', ['record' => $absensi->id]);
                        }
                        
                        // Create new attendance record
                        return static::getUrl('create') . 
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
