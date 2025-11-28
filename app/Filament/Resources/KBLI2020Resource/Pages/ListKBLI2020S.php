<?php

namespace App\Filament\Resources\KBLI2020Resource\Pages;

use App\Filament\Resources\KBLI2020Resource;
use App\Models\KBLI2020;
use Filament\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ListKBLI2020s extends ListRecords // ← pastikan 's' kecil, sama seperti di Resource
{
    protected static string $resource = KBLI2020Resource::class;

    protected function getHeaderActions(): array
    {
        return [
            // 📥 Download Template
            Actions\Action::make('downloadTemplate')
                ->label('Download Template KBLI')
                ->icon('heroicon-o-arrow-down-tray')
                ->url(fn() => Storage::disk('public')->url('templates/kbli2020.xlsx'))
                ->openUrlInNewTab(),

            // 📤 Import dari Excel/CSV (header-aware)
            Actions\Action::make('importContoh')
                ->label('Import Contoh (Excel/CSV)')
                ->icon('heroicon-o-arrow-up-tray')
                ->form([
                    FileUpload::make('file')
                        ->label('File Excel/CSV')
                        ->required()
                        ->disk('public')
                        ->directory('imports')
                        ->acceptedFileTypes([
                            'text/csv',
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        ]),
                    // Toggle append dihapus, default behavior sekarang selalu append

                    TextInput::make('delimiter')
                        ->label('Pemisah multi-contoh di sel')
                        ->helperText('Jika satu sel berisi banyak contoh, pisahkan dengan tanda ini. Mis: ;')
                        ->default(';')
                        ->maxLength(2),
                ])
                ->action(function (array $data) {
                    try {
                        $path = Storage::disk('public')->path($data['file']);
                        $delimiter = $data['delimiter'] ?: ';';
                        $append = true; // Selalu append
        
                        $spreadsheet = IOFactory::load($path);
                        $sheet = $spreadsheet->getActiveSheet();
                        $rows = $sheet->toArray(null, true, true, true);

                        if (empty($rows)) {
                            Notification::make()->danger()->title('File kosong')->send();
                            return;
                        }

                        // --- BACA HEADER (baris 1) ---
                        $headerRow = $rows[1];

                        $normalize = function (?string $s) {
                            $s = strtolower(trim((string) $s));
                            $s = preg_replace('/[^a-z0-9_]+/', '', $s);
                            return $s;
                        };

                        $headerMap = [];
                        foreach ($headerRow as $col => $val) {
                            $headerMap[$col] = $normalize($val);
                        }

                        // Alias untuk Kode KBLI
                        $aliasesKode = [
                            'kode',
                            'kode_kbli',
                            'kodekbli',
                            'kbli',
                            'kbli5digit',
                            '5digitkbli',
                            'kd_kbli',
                            'kode5digit',
                        ];
                        // Alias untuk Contoh Lapangan
                        $aliasesContoh = [
                            'contoh_lapangan',
                            'contohlapangan',
                            'contoh',
                            'contohnyata',
                            'kegiatan',
                            'deskripsi_lapangan'
                        ];

                        $findCol = function (array $aliases) use ($headerMap) {
                            foreach ($headerMap as $col => $hdr) {
                                if (in_array($hdr, $aliases, true))
                                    return $col;
                            }
                            return null;
                        };

                        $colKode = $findCol($aliasesKode);
                        $colContoh = $findCol($aliasesContoh);

                        // Fallback ke A dan B jika header tidak ketemu
                        if (!$colKode && isset($rows[2]['A']))
                            $colKode = 'A';
                        if (!$colContoh && isset($rows[2]['B']))
                            $colContoh = 'B';

                        if (!$colKode) {
                            Notification::make()->danger()->title('Kolom kode KBLI tidak ditemukan (cek header: "Kode KBLI" / "kbli").')->send();
                            return;
                        }

                        $updated = 0;
                        $skipped = 0;
                        $missing = 0;

                        foreach ($rows as $idx => $row) {
                            if ($idx === 1)
                                continue; // skip header
        
                            $kode = isset($row[$colKode]) ? trim((string) $row[$colKode]) : '';
                            if ($kode === '') {
                                $skipped++;
                                continue;
                            }

                            // Normalisasi: pastikan 5 digit (misal 1111 -> 01111)
                            // Hati-hati: kalau kode aslinya memang pendek (misal 2 digit), logic ini akan padding.
                            // Sesuaikan dengan data KBLI Anda. Biasanya KBLI 2020 itu 5 digit.
                            if (ctype_digit($kode) && strlen($kode) < 5) {
                                $kode = str_pad($kode, 5, '0', STR_PAD_LEFT);
                            }

                            $contohCell = ($colContoh && isset($row[$colContoh])) ? trim((string) $row[$colContoh]) : '';
                            $contohList = [];
                            if ($contohCell !== '') {
                                $parts = array_map('trim', explode($delimiter, $contohCell));
                                $contohList = array_values(array_filter($parts, fn($v) => $v !== ''));
                            }

                            // Cari dokumen KBLI (pastikan field di DB adalah 'kode_5_digit' atau 'kode' sesuai schema)
                            // Di kode sebelumnya pakai 'kode_5_digit'.
                            $doc = KBLI2020::where('kode_5_digit', $kode)->first();

                            // Fallback coba cari tanpa padding atau field lain jika perlu
                            if (!$doc) {
                                $missing++;
                                continue;
                            }

                            $current = is_array($doc->contoh_lapangan ?? null) ? $doc->contoh_lapangan : [];

                            if ($append) {
                                $doc->contoh_lapangan = array_values(array_unique(array_merge($current, $contohList)));
                            } else {
                                $doc->contoh_lapangan = $contohList;
                            }

                            $doc->last_updated_by = auth()->user()->nip ?? (string) auth()->id() ?? 'import';
                            $doc->save();
                            $updated++;
                        }

                        Notification::make()
                            ->success()
                            ->title("Import selesai")
                            ->body("Updated: {$updated}, Missing kode: {$missing}, Skipped: {$skipped}")
                            ->send();

                    } catch (\Throwable $e) {
                        Notification::make()
                            ->danger()
                            ->title('Import gagal')
                            ->body($e->getMessage())
                            ->send();
                    }
                })
                ->requiresConfirmation()
                ->modalSubmitActionLabel('Proses Import'),
        ];
    }
}
