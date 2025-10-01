<?php

namespace App\Filament\Resources\JSONParseResource\Pages;

use App\Filament\Resources\JSONParseResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditJSONParse extends EditRecord
{
    protected static string $resource = JSONParseResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
