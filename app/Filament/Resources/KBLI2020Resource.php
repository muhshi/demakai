<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KBLI2020Resource\Pages;
use App\Filament\Resources\KBLI2020Resource\RelationManagers;
use App\Models\KBLI2020;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class KBLI2020Resource extends Resource
{
    protected static ?string $model = KBLI2020::class;
    protected static ?string $navigationGroup = 'Klasifikasi';
    protected static ?string $navigationLabel = 'KBLI 2020';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('kode_5_digit')
                ->label('Kode 5 Digit')->required()->maxLength(10),
            Forms\Components\TextInput::make('judul')
                ->label('Judul')->required()->maxLength(300),
            Forms\Components\Textarea::make('deskripsi')
                ->label('Deskripsi')->rows(8),
            Forms\Components\TextInput::make('sektor')
                ->maxLength(5),
            Forms\Components\TextInput::make('id')
                ->numeric()->label('ID (opsional)')->columnSpan(1),
            Forms\Components\TextInput::make('kode_4_digit_id')
                ->numeric()->label('Kode 4 Digit ID')->columnSpan(1),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('kode_5_digit')
                    ->label('Kode')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('judul')
                    ->searchable()->wrap()->limit(60),
                Tables\Columns\TextColumn::make('sektor')
                    ->badge()->color('info')->toggleable(),
                Tables\Columns\TextColumn::make('deskripsi')
                    ->wrap()->limit(120)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('kode_5_digit')   // <- pastikan field ada
            ->paginated([25, 50, 100]);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['kode_5_digit', 'judul', 'deskripsi', 'sektor'];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKBLI2020s::route('/'),
            'create' => Pages\CreateKBLI2020::route('/create'),
            'edit' => Pages\EditKBLI2020::route('/{record}/edit'),
        ];
    }
}
