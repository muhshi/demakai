<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class KBLI2020TemplateExport implements FromCollection, WithHeadings, WithStyles
{
    public function collection()
    {
        return collect([]);
    }

    public function headings(): array
    {
        return [
            'Kode KBLI 2020',
            'Contoh Lapangan (Pisahkan dengan ;)'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
