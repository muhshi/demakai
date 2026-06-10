<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\FieldExampleSubmission;

class PendingSubmissionsStats extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        return [
            Stat::make('Pending Pengajuan', FieldExampleSubmission::where('status', 'pending')->count())
                ->description('Jumlah pengajuan contoh lapangan yang menunggu persetujuan')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
        ];
    }
}
