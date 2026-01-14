<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KBLI2025Resource\Pages;
use App\Models\KBLI2025;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class KBLI2025Resource extends Resource
{
    protected static ?string $model = KBLI2025::class;
    protected static ?string $navigationGroup = 'Klasifikasi';
    protected static ?string $navigationLabel = 'KBLI 2025';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('Kode')
                ->label('Kode')
                ->required()
                ->disabled()
                ->maxLength(10),
            Forms\Components\TextInput::make('Judul')
                ->label('Judul')
                ->required()
                ->disabled()
                ->maxLength(300),
            Forms\Components\Textarea::make('Deskripsi')
                ->label('Deskripsi')
                ->disabled()
                ->rows(6),
            Forms\Components\TagsInput::make('contoh_lapangan')
                ->label('Contoh Lapangan')
                ->placeholder('Tambah contoh lalu Enter')
                ->reorderable()
                ->separator(','),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                    Tables\Columns\TextColumn::make('Kode')
                        ->label('Kode')->searchable()->sortable(),
                    Tables\Columns\TextColumn::make('Kategori')
                        ->label('Kategori')->badge()->color('info')->toggleable(),
                    Tables\Columns\TextColumn::make('Judul')
                        ->searchable()->wrap()->limit(60),
                    Tables\Columns\TextColumn::make('contoh_lapangan')
                        ->label('Contoh Lapangan')
                        ->wrap()
                        ->limit(150)
                        ->toggleable(isToggledHiddenByDefault: false)
                        ->searchable(),
                    Tables\Columns\TextColumn::make('Deskripsi')
                        ->wrap()->limit(120)
                        ->toggleable(isToggledHiddenByDefault: true),
                ])
            ->defaultSort('Kode')
            ->paginated([25, 50, 100]);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['Kode', 'Judul', 'Deskripsi', 'Kategori'];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKBLI2025s::route('/'),
            'edit' => Pages\EditKBLI2025::route('/{record}/edit'),
        ];
    }
}
