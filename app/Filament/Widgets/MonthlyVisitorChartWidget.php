<?php

namespace App\Filament\Widgets;

use App\Models\PageVisit;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class MonthlyVisitorChartWidget extends ChartWidget
{
    protected ?string $heading = 'Statistik Pengunjung Bulanan';
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 1;

    protected function getData(): array
    {
        // Ambil data statistik kunjungan 6 bulan terakhir
        $visits = PageVisit::selectRaw("TO_CHAR(created_at, 'YYYY-MM') as ym, COUNT(*) as total_visits, COUNT(DISTINCT ip_address) as unique_ips")
            ->where('created_at', '>=', now()->subMonths(6)->startOfMonth())
            ->groupBy('ym')
            ->orderBy('ym')
            ->get();

        $labels = [];
        $totalVisitsData = [];
        $uniqueIpsData = [];

        foreach ($visits as $row) {
            $date = Carbon::createFromFormat('Y-m', $row->ym);
            $labels[] = $date->translatedFormat('M Y');
            $totalVisitsData[] = (int) $row->total_visits;
            $uniqueIpsData[] = (int) $row->unique_ips;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Total Kunjungan',
                    'data' => $totalVisitsData,
                    'backgroundColor' => 'rgba(99, 102, 241, 0.85)',
                    'borderColor' => '#6366f1',
                    'borderWidth' => 1,
                    'borderRadius' => 6,
                ],
                [
                    'label' => 'Pengunjung Unik (IP)',
                    'data' => $uniqueIpsData,
                    'backgroundColor' => 'rgba(16, 185, 129, 0.85)',
                    'borderColor' => '#10b981',
                    'borderWidth' => 1,
                    'borderRadius' => 6,
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
                    'position' => 'top',
                ],
            ],
            'scales' => [
                'x' => [
                    'grid' => [
                        'display' => false,
                    ],
                ],
                'y' => [
                    'grid' => [
                        'color' => '#f3f4f6',
                    ],
                    'beginAtZero' => true,
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
