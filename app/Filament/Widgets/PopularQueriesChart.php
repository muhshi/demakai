<?php

namespace App\Filament\Widgets;

use App\Models\SearchHistory;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class PopularQueriesChart extends ChartWidget
{
    protected ?string $heading = 'Pencarian Terpopuler (Semua)';
    protected static ?int $sort = 5;
    protected int | string | array $columnSpan = 1;

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
                    'backgroundColor' => 'rgba(99, 102, 241, 0.8)',
                    'borderRadius' => 6,
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
