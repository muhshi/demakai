<?php

namespace App\Filament\Resources\FieldExampleSubmissionResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\FieldExampleSubmissionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

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
            'Semua' => \Filament\Resources\Components\Tab::make(),
            'KBLI 2025' => \Filament\Resources\Components\Tab::make()
                ->modifyQueryUsing(fn (\Illuminate\Database\Eloquent\Builder $query) => $query->where('type', 'KBLI 2025')),
            'KBLI 2020' => \Filament\Resources\Components\Tab::make()
                ->modifyQueryUsing(fn (\Illuminate\Database\Eloquent\Builder $query) => $query->where('type', 'KBLI 2020')),
            'KBJI 2014' => \Filament\Resources\Components\Tab::make()
                ->modifyQueryUsing(fn (\Illuminate\Database\Eloquent\Builder $query) => $query->where('type', 'KBJI 2014')),
        ];
    }
}
