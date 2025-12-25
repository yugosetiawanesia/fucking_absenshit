<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SemesterResource\Pages;
use App\Models\Semester;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SemesterResource extends Resource
{
    protected static ?string $model = Semester::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationLabel = 'Semester';

    protected static ?string $navigationGroup = 'Master Absensi';

    protected static ?int $navigationSort = 5;

    protected static ?string $modelLabel = 'Semester';

    protected static ?string $pluralModelLabel = 'Semester';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('tahun_ajaran')
                    ->required()
                    ->placeholder('2024/2025')
                    ->maxLength(20),
                Forms\Components\Select::make('semester')
                    ->required()
                    ->options([
                        'ganjil' => 'Ganjil',
                        'genap' => 'Genap',
                    ]),
                Forms\Components\DatePicker::make('tanggal_mulai')
                    ->required(),
                Forms\Components\DatePicker::make('tanggal_selesai')
                    ->required(),
                Forms\Components\Toggle::make('is_active')
                    ->label('Semester Aktif')
                    ->default(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tahun_ajaran')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('semester')
                    ->formatStateUsing(fn (?string $state) => $state === 'genap' ? 'Genap' : 'Ganjil')
                    ->sortable(),
                Tables\Columns\TextColumn::make('tanggal_mulai')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tanggal_selesai')
                    ->date()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSemesters::route('/'),
            'create' => Pages\CreateSemester::route('/create'),
            'view' => Pages\ViewSemester::route('/{record}'),
            'edit' => Pages\EditSemester::route('/{record}/edit'),
        ];
    }
}
