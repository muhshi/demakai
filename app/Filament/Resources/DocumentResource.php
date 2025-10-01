<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DocumentResource\Pages;
use App\Models\JSONParse;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DocumentResource extends Resource
{
    protected static ?string $model = JSONParse::class;

    protected static ?string $navigationGroup = 'Parsing Documents';
    protected static ?string $modelLabel = 'Manage Documents';
    protected static ?string $pluralModelLabel = 'Manage Documents';
    protected static ?string $navigationLabel = 'Manage Documents';
    protected static ?string $navigationIcon  = 'heroicon-o-document-text';
    protected static ?string $slug            = 'documents';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Judul')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('source_type')
                    ->label('Sumber Data'),

                Tables\Columns\TextColumn::make('year')
                    ->label('Tahun')
                    ->sortable(),

                Tables\Columns\TextColumn::make('similarity_score')
                    ->label('Similarity')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state * 100, 2).'%' : '-'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('title')->required(),
            Forms\Components\Select::make('source_type')
                ->options([
                    'publikasi' => 'Publikasi BPS',
                    'paper_penelitian' => 'Paper Penelitian',
                    'data' => 'Data Statistik',
                ])->required(),
            Forms\Components\TextInput::make('authors')->label('Penulis/Instansi'),
            Forms\Components\TextInput::make('year')->numeric(),
            Forms\Components\TextInput::make('doi'),
            Forms\Components\TagsInput::make('tags'),

            Forms\Components\Repeater::make('chunks')
                ->schema([
                    Forms\Components\TextInput::make('chunk_id')->disabled(),
                    Forms\Components\Textarea::make('text')->rows(3)->disabled(),
                ])
                ->collapsed()
                ->disableItemCreation()
                ->disableItemDeletion()
                ->label('Parsed Chunks (Preview)')
                ->visibleOn('edit'),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDocuments::route('/'),
            'edit'  => Pages\EditDocument::route('/{record}/edit'),
        ];
    }
}
