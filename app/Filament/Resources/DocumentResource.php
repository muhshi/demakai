<?php

namespace App\Filament\Resources;

use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use App\Filament\Resources\DocumentResource\Pages\ListDocuments;
use App\Filament\Resources\DocumentResource\Pages\EditDocument;
use App\Filament\Resources\DocumentResource\Pages;
use App\Models\JSONParse;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DocumentResource extends Resource
{
    protected static ?string $model = JSONParse::class;

    protected static string | \UnitEnum | null $navigationGroup = 'Parsing Documents';
    protected static ?string $modelLabel = 'Manage Documents';
    protected static ?string $pluralModelLabel = 'Manage Documents';
    protected static ?string $navigationLabel = 'Manage Documents';
    protected static string | \BackedEnum | null $navigationIcon  = 'heroicon-o-document-text';
    protected static ?string $slug            = 'documents';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Judul')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('source_type')
                    ->label('Sumber Data')
                    ->badge(),

                TextColumn::make('year')
                    ->label('Tahun')
                    ->sortable(),

                TextColumn::make('similarity_score')
                    ->label('Similarity')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state * 100, 2).'%' : '-'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->required(),
            Select::make('source_type')
                ->options([
                    'publikasi' => 'Publikasi BPS',
                    'paper_penelitian' => 'Paper Penelitian',
                    'data' => 'Data Statistik',
                ])->required(),
            TextInput::make('authors')->label('Penulis/Instansi'),
            TextInput::make('year')->numeric(),
            TextInput::make('doi'),
            TagsInput::make('tags'),

            Repeater::make('chunks')
                ->schema([
                    TextInput::make('chunk_id')->disabled(),
                    Textarea::make('text')->rows(3)->disabled(),
                ])
                ->collapsed()
                ->addable(false)
                ->deletable(false)
                ->label('Parsed Chunks (Preview)')
                ->visibleOn('edit'),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDocuments::route('/'),
            'edit'  => EditDocument::route('/{record}/edit'),
        ];
    }
}
