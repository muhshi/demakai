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

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // hanya kirim field yang boleh diubah
        $data = [
            'contoh_lapangan' => $data['contoh_lapangan'] ?? [],
            // kalau kamu ingin simpan catatan_internal juga, hilangkan disabled di form & tambahkan di sini
            // 'catatan_internal' => $data['catatan_internal'] ?? null,
            'last_updated_by' => auth()->user()->nip ?? (string) auth()->id(),
        ];
        return $data;
    }
}
