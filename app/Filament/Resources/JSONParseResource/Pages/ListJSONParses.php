<?php

namespace App\Filament\Resources\JSONParseResource\Pages;

use App\Filament\Resources\JSONParseResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListJSONParses extends ListRecords
{
    protected static string $resource = JSONParseResource::class;

    protected function getHeaderActions(): array
    {
        return [ ];
    }

    public function mount(): void
    {
        $this->redirect(JSONParseResource::getUrl('create'));
    }
}
