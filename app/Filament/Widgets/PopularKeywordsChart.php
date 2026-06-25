<?php

namespace App\Filament\Widgets;

use App\Models\SearchHistory;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class PopularKeywordsChart extends ChartWidget
{
    protected ?string $heading = 'Keyword Paling Sering Dicari';
    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = 1;

    protected function getData(): array
    {
        $data = SearchHistory::select('query', DB::raw('count(*) as count'))
            ->where('query', 'GLOB', '*[a-zA-Z]*')
            ->groupBy('query')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Pencarian',
                    'data' => $data->pluck('count'),
                    'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
                    'borderColor' => 'rgba(54, 162, 235, 1)',
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
