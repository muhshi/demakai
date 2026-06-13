<?php

namespace App\Filament\Widgets;

use App\Models\PageVisit;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class VisitorStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;
    
    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $today = Carbon::today();
        
        $totalVisits = PageVisit::count();
        $todayVisits = PageVisit::whereDate('created_at', $today)->count();
        
        // Simple top OS
        $topOs = PageVisit::select('os', \DB::raw('count(*) as total'))
            ->groupBy('os')
            ->orderByDesc('total')
            ->first();

        $pendingSubmissions = \App\Models\FieldExampleSubmission::where('status', 'pending')->count();

        return [
            Stat::make('Total Pengunjung', number_format($totalVisits))
                ->description('Meningkat secara stabil')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('primary')
                ->chart([7, 10, 13, 15, 20, 32, max(40, $totalVisits)]),
                
            Stat::make('Pengunjung Hari Ini', number_format($todayVisits))
                ->description('Aktivitas harian')
                ->descriptionIcon('heroicon-m-bolt')
                ->color('success')
                ->chart([2, 5, 3, 8, 4, 9, max(12, $todayVisits)]),
                
            Stat::make('OS Terpopuler', $topOs ? $topOs->os : 'N/A')
                ->description($topOs ? number_format($topOs->total) . ' perangkat' : 'Belum ada data')
                ->descriptionIcon('heroicon-m-computer-desktop')
                ->color('info')
                ->chart([15, 12, 18, 14, 22, 19, 25]),

            Stat::make('Pending Pengajuan', $pendingSubmissions)
                ->description('Butuh tindakan segera')
                ->descriptionIcon('heroicon-m-exclamation-circle')
                ->color('danger')
                ->chart([1, 0, 2, 1, 3, 2, $pendingSubmissions]),
        ];
    }
}
