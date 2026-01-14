<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class KBJI2014TemplateExport implements FromCollection, WithHeadings, WithStyles
{
    public function collection()
    {
        return collect([]);
    }

    public function headings(): array
    {
        return [
            'Kode KBJI 2014',
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
