<?php

namespace App\Filament\Widgets;

use App\Models\SearchHistory;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class PopularQueriesChart extends ChartWidget
{
    protected static ?string $heading = 'Pencarian Terpopuler';
    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $data = SearchHistory::select('query', DB::raw('count(*) as count'))
            ->groupBy('query')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Pencarian',
                    'data' => $data->pluck('count'),
                ],
            ],
            'labels' => $data->pluck('query'),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
