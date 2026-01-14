<?php

namespace App\Filament\Resources\KBLI2025Resource\Pages;

use App\Filament\Resources\KBLI2025Resource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditKBLI2025 extends EditRecord
{
    protected static string $resource = KBLI2025Resource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
