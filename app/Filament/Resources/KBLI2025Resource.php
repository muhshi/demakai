<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TagsInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use App\Services\SearchService;
use App\Filament\Resources\KBLI2025Resource\Pages\ListKBLI2025s;
use App\Filament\Resources\KBLI2025Resource\Pages\EditKBLI2025;
use App\Filament\Resources\KBLI2025Resource\Pages;
use App\Models\PgKBLI2025;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class KBLI2025Resource extends Resource
{
    protected static ?string $model = PgKBLI2025::class;
    protected static string | \UnitEnum | null $navigationGroup = 'Klasifikasi';
    protected static ?string $navigationLabel = 'KBLI 2025';
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('kode')
                ->label('Kode')
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
            TagsInput::make('contoh_lapangan')
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
                TextColumn::make('kode')
                    ->label('Kode')->searchable()->sortable(),
                TextColumn::make('kategori')
                    ->label('Kategori')->badge()->color('info')->toggleable(),
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
            ->defaultSort('kode')
            ->paginated([25, 50, 100])
            ->filters([
                Filter::make('ai_search')
                    ->form([
                        TextInput::make('query')
                            ->label('AI Smart Search')
                            ->placeholder('Deskripsikan bisnis Anda...')
                            ->helperText('Mencari berdasarkan makna (Semantic Search)'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (empty($data['query'])) {
                            return $query;
                        }

                        // Call Hybrid Search
                        /** @var SearchService $service */
                        $service = app(SearchService::class);

                        // Limit 100 results for relevancy
                        $results = $service->search($data['query'], 100, 'KBLI');

                        // Get IDs from results (which are Models)
                        $ids = collect($results)->pluck('id')->toArray();

                        if (empty($ids)) {
                            return $query->whereRaw('1 = 0');
                        }

                        // Filter by IDs and preserve order (PostgreSQL specific)
                        $idsString = implode(',', $ids);
                        return $query->whereIn('id', $ids)
                            ->orderByRaw("array_position(ARRAY[$idsString], id)");
                    }),
            ]);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['kode', 'judul', 'deskripsi', 'kategori'];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListKBLI2025s::route('/'),
            'edit' => EditKBLI2025::route('/{record}/edit'),
        ];
    }
}
