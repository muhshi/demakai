<?php

namespace App\Filament\Widgets;

use App\Models\SearchHistory;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class PopularCodesChart extends ChartWidget
{
    protected ?string $heading = 'Kode Paling Sering Dicari';
    protected static ?int $sort = 5;
    protected int | string | array $columnSpan = 1;

    protected function getData(): array
    {
        $data = SearchHistory::select('query', DB::raw('count(*) as count'))
            ->where('query', 'NOT GLOB', '*[a-zA-Z]*')
            ->groupBy('query')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Pencarian',
                    'data' => $data->pluck('count'),
                    'backgroundColor' => 'rgba(255, 99, 132, 0.2)',
                    'borderColor' => 'rgba(255, 99, 132, 1)',
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
