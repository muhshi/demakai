<?php

namespace App\Filament\Widgets;

use App\Models\SearchHistory;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class SearchMethodDistributionChart extends ChartWidget
{
    protected ?string $heading = 'Distribusi Metode Pencarian (Hybrid Search)';
    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = 1;

    protected function getData(): array
    {
        $data = SearchHistory::select('detected_type', DB::raw('count(*) as count'))
            ->groupBy('detected_type')
            ->orderByDesc('count')
            ->get();

        $labels = [];
        $counts = [];

        $typeLabels = [
            'keyword' => 'Kata Kunci (Keyword)',
            'semantic' => 'AI Semantik (Kalimat Utuh)',
            'exact' => 'Persis (Exact Code)',
        ];

        $colors = [
            'keyword' => '#3b82f6',   // Blue
            'semantic' => '#8b5cf6',  // Purple (AI)
            'exact' => '#10b981',     // Green
        ];

        $bgColors = [];

        foreach ($data as $item) {
            $type = $item->detected_type ?? 'semantic';
            $labels[] = $typeLabels[$type] ?? ucfirst($type);
            $counts[] = (int) $item->count;
            $bgColors[] = $colors[$type] ?? '#6b7280';
        }

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Pencarian',
                    'data' => $counts,
                    'backgroundColor' => $bgColors,
                    'hoverOffset' => 4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
