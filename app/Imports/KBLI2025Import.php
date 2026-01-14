<?php

namespace App\Imports;

use App\Models\KBLI2025;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class KBLI2025Import implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            'kode_5_digit' => new KBLI2025SheetImport(),
        ];
    }
}

class KBLI2025SheetImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            // Check if kode is present to avoid importing empty rows
            if (!empty($row['kode'])) {
                KBLI2025::create([
                    'sektor' => $row['kategori'] ?? null,
                    'kode' => (string) ($row['kode'] ?? ''),
                    'judul' => $row['judul'] ?? null,
                    'deskripsi' => $row['deskripsi'] ?? null,
                    'contoh_lapangan' => [],
                    'sumber' => 'KBLI 2025',
                ]);
            }
        }
    }
}
