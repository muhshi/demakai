<?php

namespace App\Filament\Resources;

use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TagsInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\Filter;
use App\Services\SearchService;
use App\Filament\Resources\KBJI2014Resource\Pages\ListKBJI2014s;
use App\Filament\Resources\KBJI2014Resource\Pages\CreateKBJI2014;
use App\Filament\Resources\KBJI2014Resource\Pages\EditKBJI2014;
use App\Filament\Resources\KBJI2014Resource\Pages;
use App\Filament\Resources\KBJI2014Resource\RelationManagers;
use App\Models\PgKBJI2014;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class KBJI2014Resource extends Resource
{
    protected static ?string $model = PgKBJI2014::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Klasifikasi';
    protected static ?string $navigationLabel = 'KBJI 2014';

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('kode')
                ->label('KBJI')->disabled()->dehydrated(false),
            TextInput::make('judul')
                ->label('Judul')->disabled()->dehydrated(false),
            Textarea::make('deskripsi')
                ->label('Deskripsi')->disabled()->dehydrated(false),

            TagsInput::make('contoh_lapangan')
                ->label('Contoh Lapangan')
                ->separator(';')
                ->placeholder('Tambah contoh lalu Enter'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('kode')
                    ->label('KBJI')
                    ->formatStateUsing(
                        fn($state) => strlen((string) $state) > 4
                        ? substr((string) $state, -4)
                        : $state
                    )
                    ->sortable()
                    ->searchable(),
                TextColumn::make('judul')
                    ->label('Judul')
                    ->wrap()
                    ->limit(80)
                    ->searchable(),
                TextColumn::make('deskripsi')
                    ->label('Deskripsi')
                    ->wrap()
                    ->limit(150)
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->searchable(),
                TextColumn::make('contoh_lapangan')
                    ->label('Contoh Lapangan')
                    ->wrap()
                    ->limit(150)
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->searchable(),
            ])
            ->paginated([25, 50, 100])
            ->filters([
                TernaryFilter::make('only4digit')
                    ->label('Hanya 4 digit')
                    ->queries(
                        true: fn($q) => $q->where('level', '3'), // Assuming level is stored as string '3'
                        false: fn($q) => $q, // semua
                        blank: fn($q) => $q,
                    ),
                Filter::make('ai_search')
                    ->schema([
                        TextInput::make('query')
                            ->label('AI Smart Search')
                            ->placeholder('Deskripsikan pekerjaan...')
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
                        $results = $service->search($data['query'], 100, 'KBJI');

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
        return ['judul', 'deskripsi', 'kode'];
    }

    public static function getEloquentQuery(): Builder
    {
        // hanya tampilkan unit group (4 digit)
        return parent::getEloquentQuery()->where('level', '3');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListKBJI2014s::route('/'),
            'create' => CreateKBJI2014::route('/create'),
            'edit' => EditKBJI2014::route('/{record}/edit'),
        ];
    }
}
