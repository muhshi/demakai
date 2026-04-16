<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TagsInput;
use Filament\Tables\Columns\TextColumn;
use App\Filament\Resources\KBLI2020Resource\Pages\ListKBLI2020s;
use App\Filament\Resources\KBLI2020Resource\Pages\EditKBLI2020;
use App\Filament\Resources\KBLI2020Resource\Pages;
use App\Filament\Resources\KBLI2020Resource\RelationManagers;
use App\Models\PgKBLI2020;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class KBLI2020Resource extends Resource
{
    protected static ?string $model = PgKBLI2020::class;
    protected static string | \UnitEnum | null $navigationGroup = 'Klasifikasi';
    protected static ?string $navigationLabel = 'KBLI 2020';
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            // Field yang sudah ada...
            TextInput::make('kode')
                ->label('Kode 5 Digit')
                ->required()
                ->disabled()
                ->maxLength(10),
            TextInput::make('judul')
                ->label('Judul')
                ->required()
                ->disabled()
                ->maxLength(300),
            Textarea::make('deskripsi')
                ->label('Deskripsi')
                ->disabled()
                ->rows(6),

            // 🔹 Field baru:
            TagsInput::make('contoh_lapangan')
                ->label('Contoh Lapangan')
                ->placeholder('Tambah contoh lalu Enter')
                ->reorderable()
                ->separator(','),

            // Forms\Components\Textarea::make('catatan_internal')
            //     ->label('Catatan Internal')
            //     ->rows(4),

            TextInput::make('last_updated_by')
                ->label('Diupdate oleh')
                ->disabled()
                ->maxLength(50),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('kode')
                    ->label('Kode')->searchable()->sortable(),
                TextColumn::make('sektor')
                    ->badge()->color('info')->toggleable(),
                TextColumn::make('judul')
                    ->searchable()->wrap()->limit(60),
                TextColumn::make('contoh_lapangan')
                    ->label('Contoh Lapangan')
                    ->wrap()
                    ->limit(150)
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->searchable(),
                TextColumn::make('deskripsi')
                    ->wrap()->limit(120)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('kode')   // <- pastikan field ada
            ->paginated([25, 50, 100]);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['kode', 'judul', 'deskripsi', 'sektor'];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListKBLI2020s::route('/'),
            //'create' => Pages\CreateKBLI2020::route('/create'),
            'edit' => EditKBLI2020::route('/{record}/edit'),
        ];
    }
}
