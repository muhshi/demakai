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
            Forms\Components\TextInput::make('title')->label('Judul/Jabatan')->required()->maxLength(300),
            Forms\Components\Textarea::make('desc')->label('Deskripsi')->rows(8),
            Forms\Components\TextInput::make('id')->maxLength(50),
            Forms\Components\TextInput::make('fid')->maxLength(50),
            Forms\Components\TextInput::make('parent_id')->maxLength(50),
            Forms\Components\TextInput::make('parent_fid')->maxLength(50),
            Forms\Components\TextInput::make('lv')->numeric(),
            Forms\Components\Toggle::make('last')->label('Leaf?'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('Jabatan')->searchable()->wrap()->limit(60),
                Tables\Columns\TextColumn::make('fid')->toggleable(),
                Tables\Columns\TextColumn::make('parent_fid')->toggleable(),
                Tables\Columns\BadgeColumn::make('lv')->label('Level')->color('info'),
                Tables\Columns\IconColumn::make('last')->boolean()->label('Leaf'),
                Tables\Columns\TextColumn::make('desc')->label('Deskripsi')->wrap()->limit(120)->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('lv')->options([0 => '0', 1 => '1', 2 => '2', 3 => '3']),
                Tables\Filters\TernaryFilter::make('last')->label('Leaf'),
            ])
            ->defaultSort('lv')
            ->paginated([25, 50, 100]);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'desc', 'fid', 'parent_fid', 'id'];
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
