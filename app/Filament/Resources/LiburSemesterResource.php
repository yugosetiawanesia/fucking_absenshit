<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LiburSemesterResource\Pages;
use App\Models\LiburSemester;
use App\Models\Semester;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LiburSemesterResource extends Resource
{
    protected static ?string $model = LiburSemester::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $navigationLabel = 'Hari Libur Semester';

    protected static ?string $navigationGroup = 'Master Absensi';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Libur Semester')
                    ->description('Atur libur semester dengan rentang tanggal')
                    ->schema([
                        Forms\Components\TextInput::make('nama_libur')
                            ->label('Nama Libur')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Contoh: Libur Akhir Semester, Libur Semester Ganjil'),
                        
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('tanggal_mulai')
                                    ->label('Tanggal Mulai')
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(fn ($state, callable $set) => $set('tanggal_selesai', $state)),
                                
                                Forms\Components\DatePicker::make('tanggal_selesai')
                                    ->label('Tanggal Selesai')
                                    ->required()
                                    ->afterOrEqual('tanggal_mulai'),
                            ]),
                        
                        Forms\Components\Select::make('semester_id')
                            ->label('Semester')
                            ->relationship('semester', 'semester')
                            ->getOptionLabelFromRecordUsing(function (Semester $record) {
                                return ucfirst($record->semester) . ' ' . $record->tahun_ajaran;
                            })
                            ->placeholder('Pilih Semester (opsional)'),
                        
                        Forms\Components\Textarea::make('keterangan')
                            ->label('Keterangan')
                            ->rows(3)
                            ->placeholder('Tambahkan keterangan atau catatan tambahan...'),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama_libur')
                    ->label('Nama Libur')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('tanggal_mulai')
                    ->label('Mulai')
                    ->date('d M Y')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('tanggal_selesai')
                    ->label('Selesai')
                    ->date('d M Y')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('durasi')
                    ->label('Durasi')
                    ->getStateUsing(function (LiburSemester $record): string {
                        $mulai = $record->tanggal_mulai;
                        $selesai = $record->tanggal_selesai;
                        $days = $mulai->diffInDays($selesai) + 1;
                        return $days . ' hari';
                    })
                    ->sortable(false),
                
                Tables\Columns\TextColumn::make('semester.semester')
                    ->label('Semester')
                    ->getStateUsing(function (LiburSemester $record): ?string {
                        if (!$record->semester) return null;
                        return ucfirst($record->semester->semester) . ' ' . $record->semester->tahun_ajaran;
                    })
                    ->placeholder('-'),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('semester_id')
                    ->label('Semester')
                    ->relationship('semester', 'semester')
                    ->getOptionLabelFromRecordUsing(function (Semester $record) {
                        return ucfirst($record->semester) . ' ' . $record->tahun_ajaran;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListLiburSemesters::route('/'),
            'create' => Pages\CreateLiburSemester::route('/create'),
            'edit' => Pages\EditLiburSemester::route('/{record}/edit'),
        ];
    }
}
