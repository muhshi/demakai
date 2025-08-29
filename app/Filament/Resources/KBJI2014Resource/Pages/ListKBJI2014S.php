<?php

namespace App\Filament\Resources\KBJI2014Resource\Pages;

use App\Filament\Resources\KBJI2014Resource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListKBJI2014S extends ListRecords
{
    protected static string $resource = KBJI2014Resource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
