<?php

namespace App\Filament\Resources\KBJI2014Resource\Pages;

use App\Filament\Resources\KBJI2014Resource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditKBJI2014 extends EditRecord
{
    protected static string $resource = KBJI2014Resource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Remove embedding from form data - Livewire can't handle Vector type
        unset($data['embedding']);
        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
