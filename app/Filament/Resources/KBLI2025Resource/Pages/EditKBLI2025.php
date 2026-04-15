<?php

namespace App\Filament\Resources\KBLI2025Resource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\KBLI2025Resource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditKBLI2025 extends EditRecord
{
    protected static string $resource = KBLI2025Resource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Remove embedding from form data - Livewire can't handle Vector type
        unset($data['embedding']);
        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
