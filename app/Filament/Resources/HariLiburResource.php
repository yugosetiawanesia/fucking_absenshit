<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HariLiburResource\Pages;
use App\Models\HariLibur;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class HariLiburResource extends Resource
{
    protected static ?string $model = HariLibur::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Hari Libur Nasional';

    protected static ?string $navigationGroup = 'Master Absensi';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'Hari Libur';

    protected static ?string $pluralModelLabel = 'Hari Libur';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('tanggal')
                    ->required()
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('keterangan')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Toggle::make('is_nasional')
                    ->label('Libur Nasional')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tanggal')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('keterangan')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_nasional')
                    ->label('Nasional')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            'index' => Pages\ListHariLiburs::route('/'),
            'create' => Pages\CreateHariLibur::route('/create'),
            'view' => Pages\ViewHariLibur::route('/{record}'),
            'edit' => Pages\EditHariLibur::route('/{record}/edit'),
        ];
    }
}
