<?php

namespace App\Filament\Resources\UserFeedbackResource\Pages;

use App\Filament\Resources\UserFeedbackResource;
use App\Models\UserFeedback;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListUserFeedbacks extends ListRecords
{
    protected static string $resource = UserFeedbackResource::class;

    public function getTabs(): array
    {
        return [
            'Semua' => Tab::make()
                ->badge(UserFeedback::count()),

            'Baru' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'baru'))
                ->badge(UserFeedback::where('status', 'baru')->count())
                ->badgeColor('warning'),

            'Sedang Ditinjau' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'ditinjau'))
                ->badge(UserFeedback::where('status', 'ditinjau')->count())
                ->badgeColor('info'),

            'Sudah Diterapkan' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'diterapkan'))
                ->badge(UserFeedback::where('status', 'diterapkan')->count())
                ->badgeColor('success'),
        ];
    }
}
