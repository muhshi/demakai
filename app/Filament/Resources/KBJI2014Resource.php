<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KBJI2014Resource\Pages;
use App\Filament\Resources\KBJI2014Resource\RelationManagers;
use App\Models\KBJI2014;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class KBJI2014Resource extends Resource
{
    protected static ?string $model = KBJI2014::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Klasifikasi';
    protected static ?string $navigationLabel = 'KBJI 2014';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('kode_kbji')
                ->label('KBJI')->disabled()->dehydrated(false),
            Forms\Components\TextInput::make('title')
                ->label('Judul')->disabled()->dehydrated(false),
            Forms\Components\Textarea::make('desc')
                ->label('Deskripsi')->disabled()->dehydrated(false),

            Forms\Components\TagsInput::make('contoh_lapangan')
                ->label('Contoh Lapangan')
                ->separator(';')
                ->placeholder('Tambah contoh lalu Enter'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('kode_kbji')
                    ->label('KBJI')
                    ->formatStateUsing(
                        fn($state) => strlen((string) $state) > 4
                        ? substr((string) $state, -4)
                        : $state
                    )
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('title')
                    ->label('Judul')
                    ->wrap()
                    ->limit(80)
                    ->searchable(),
                Tables\Columns\TextColumn::make('desc')
                    ->label('Deskripsi')
                    ->wrap()
                    ->limit(150)
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->searchable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('only4digit')
                    ->label('Hanya 4 digit')
                    ->queries(
                        true: fn($q) => $q->where('lv', 3),
                        false: fn($q) => $q, // semua
                        blank: fn($q) => $q,
                    ),
            ])
            ->defaultSort('kode_kbji')
            ->paginated([25, 50, 100]);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'desc', 'fid', 'kode_kbji', 'parent_fid', 'id'];
    }

    public static function getEloquentQuery(): Builder
    {
        // hanya tampilkan unit group (4 digit)
        return parent::getEloquentQuery()->where('lv', 3);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKBJI2014s::route('/'),
            'create' => Pages\CreateKBJI2014::route('/create'),
            'edit' => Pages\EditKBJI2014::route('/{record}/edit'),
        ];
    }
}
