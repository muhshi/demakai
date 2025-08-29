<?php

namespace App\Filament\Resources\KBLI2020Resource\Pages;

use App\Filament\Resources\KBLI2020Resource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListKBLI2020S extends ListRecords
{
    protected static string $resource = KBLI2020Resource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
