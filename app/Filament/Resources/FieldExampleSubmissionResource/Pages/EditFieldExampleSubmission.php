<?php

namespace App\Filament\Resources\FieldExampleSubmissionResource\Pages;

use App\Filament\Resources\FieldExampleSubmissionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFieldExampleSubmission extends EditRecord
{
    protected static string $resource = FieldExampleSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
