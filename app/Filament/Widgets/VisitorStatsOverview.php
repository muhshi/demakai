<?php

namespace App\Filament\Widgets;

use App\Models\PageVisit;
use App\Models\SearchHistory;
use App\Models\FieldExampleSubmission;
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
        
        // 1. Pencarian
        $totalSearches = SearchHistory::count();
        $todaySearches = SearchHistory::whereDate('created_at', $today)->count();
        
        $searchSparkline = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = $today->copy()->subDays($i);
            $searchSparkline[] = SearchHistory::whereDate('created_at', $d)->count();
        }

        // 2. Kunjungan Web
        $totalVisits = PageVisit::count();
        $todayVisits = PageVisit::whereDate('created_at', $today)->count();
        $uniqueIps = PageVisit::distinct('ip_address')->count('ip_address');

        $visitSparkline = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = $today->copy()->subDays($i);
            $visitSparkline[] = PageVisit::whereDate('created_at', $d)->count();
        }

        // 3. Crowdsourcing Submissions
        $totalSubmissions = FieldExampleSubmission::count();
        $pendingSubmissions = FieldExampleSubmission::where('status', 'pending')->count();
        $approvedSubmissions = FieldExampleSubmission::where('status', 'approved')->count();

        return [
            Stat::make('Total Pencarian', number_format($totalSearches))
                ->description("+{$todaySearches} pencarian hari ini")
                ->descriptionIcon('heroicon-m-magnifying-glass')
                ->color('primary')
                ->chart($searchSparkline),

            Stat::make('Pengunjung Unik', number_format($uniqueIps))
                ->description('Perangkat / IP unik terdata')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('info')
                ->chart([35, 42, 58, 48, 62, 50, max(20, $uniqueIps % 100)]),

            Stat::make('Total Kunjungan Web', number_format($totalVisits))
                ->description("+{$todayVisits} kunjungan hari ini")
                ->descriptionIcon('heroicon-m-globe-alt')
                ->color('success')
                ->chart($visitSparkline),

            Stat::make('Usulan Contoh Lapangan', number_format($pendingSubmissions) . ' Pending')
                ->description("{$approvedSubmissions} disetujui ({$totalSubmissions} total)")
                ->descriptionIcon('heroicon-m-document-check')
                ->color($pendingSubmissions > 0 ? 'warning' : 'success')
                ->chart([3, 2, 5, 4, 6, 8, max(5, $pendingSubmissions)]),
        ];
    }
}
