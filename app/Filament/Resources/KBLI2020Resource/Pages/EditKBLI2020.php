<?php

namespace App\Filament\Resources\KBLI2020Resource\Pages;

use App\Filament\Resources\KBLI2020Resource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditKBLI2020 extends EditRecord
{
    protected static string $resource = KBLI2020Resource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
