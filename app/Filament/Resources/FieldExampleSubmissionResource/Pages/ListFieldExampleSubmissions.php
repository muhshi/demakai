<?php

namespace App\Filament\Resources\FieldExampleSubmissionResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\FieldExampleSubmissionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;

class ListFieldExampleSubmissions extends ListRecords
{
    protected static string $resource = FieldExampleSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'Semua' => Tab::make()
                ->badge(\App\Models\FieldExampleSubmission::count()),
            'Belum Disetujui' => Tab::make()
                ->modifyQueryUsing(fn(\Illuminate\Database\Eloquent\Builder $query) => $query->where('status', 'pending'))
                ->badge(\App\Models\FieldExampleSubmission::where('status', 'pending')->count())
                ->badgeColor('warning'),
            'Sudah Disetujui' => Tab::make()
                ->modifyQueryUsing(fn(\Illuminate\Database\Eloquent\Builder $query) => $query->where('status', 'approved'))
                ->badge(\App\Models\FieldExampleSubmission::where('status', 'approved')->count())
                ->badgeColor('success'),
        ];
    }
}
