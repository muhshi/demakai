<?php

namespace App\Filament\Resources\KBJI2014Resource\Pages;

use App\Filament\Resources\KBJI2014Resource;
use App\Models\KBJI2014;
use Filament\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ListKBJI2014s extends ListRecords
{
    protected static string $resource = KBJI2014Resource::class;

    protected function getHeaderActions(): array
    {
        return [
            // 📥 Download Template (langsung ke URL storage)
            Actions\Action::make('downloadTemplate')
                ->label('Download Template KBJI')
                ->icon('heroicon-o-arrow-down-tray')
                ->url(fn() => Storage::disk('public')->url('templates/kbji2014.xlsx'))
                ->openUrlInNewTab(),

            // 📤 Import dari Excel/CSV (header-aware: fid & contoh_lapangan)
            Actions\Action::make('importContohKBJI')
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
                        ->label('Pemisah multi-contoh di satu sel')
                        ->helperText('Jika satu sel berisi beberapa contoh, pisahkan dengan tanda ini. Mis: ;')
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

                        // normalizer header: lowercase, buang non-alnum
                        $normalize = function (?string $s) {
                            $s = strtolower(trim((string) $s));
                            $s = preg_replace('/[^a-z0-9_]+/', '', $s); // hapus spasi/tanda baca
                            return $s;
                        };

                        // map huruf kolom => header-normalized
                        $headerMap = [];
                        foreach ($headerRow as $col => $val) {
                            $headerMap[$col] = $normalize($val);
                        }
                        // alias yg diterima
                        $aliasesKode = [
                            'kode_kbji',
                            'kodekbji',
                            'kbji',
                            'kbji4digit',
                            '4digitkbji',
                            'kd_kbji',
                            'kode',
                            'kode4digit',
                            'kodejabatan' // tambah kalau perlu
                        ];
                        $aliasesContoh = [
                            'contoh_lapangan',
                            'contohlapangan',
                            'contoh',
                            'contohnyata'
                        ];

                        // helper cari kolom by alias list
                        $findCol = function (array $aliases) use ($headerMap) {
                            foreach ($headerMap as $col => $hdr) {
                                if (in_array($hdr, $aliases, true))
                                    return $col;
                            }
                            return null;
                        };

                        $colKode = $findCol($aliasesKode);
                        $colContoh = $findCol($aliasesContoh);

                        // fallback kalau tetap null → coba asumsi kolom A/B
                        if (!$colKode && isset($rows[2]['A']))
                            $colKode = 'A';
                        if (!$colContoh && isset($rows[2]['B']))
                            $colContoh = 'B';

                        if (!$colKode) {
                            Notification::make()->danger()->title('Kolom kode KBJI tidak ditemukan (cek header: "4 Digit KBJI" / "kode_kbji").')->send();
                            return;
                        }

                        $updated = 0;
                        $missing = 0;
                        $skipped = 0;

                        foreach ($rows as $i => $row) {
                            if ($i === 1)
                                continue;

                            $kode = isset($row[$colKode]) ? trim((string) $row[$colKode]) : '';
                            if ($kode === '') {
                                $skipped++;
                                continue;
                            }

                            // Normalisasi: 1–4 digit, tapi praktiknya kamu akan pakai 4 digit
                            if (ctype_digit($kode) && strlen($kode) > 4) {
                                $kode = substr($kode, -4); // ambil 4 terakhir kalau ada yang kepanjangan
                            }

                            $contohCell = $colContoh ? trim((string) ($row[$colContoh] ?? '')) : '';
                            $contohList = $contohCell !== ''
                                ? array_values(array_filter(array_map('trim', explode($delimiter, $contohCell)), fn($v) => $v !== ''))
                                : [];

                            $doc = KBJI2014::where('kode_kbji', $kode)->first();   // <— GANTI: cari pakai kode_kbji
                            if (!$doc) {
                                $missing++;
                                continue;
                            }

                            $current = is_array($doc->contoh_lapangan ?? null) ? $doc->contoh_lapangan : [];
                            $doc->contoh_lapangan = $append
                                ? array_values(array_unique(array_merge($current, $contohList)))
                                : $contohList;

                            $doc->last_updated_by = auth()->user()->nip ?? (string) auth()->id() ?? 'import';
                            $doc->save();
                            $updated++;
                        }

                        Notification::make()
                            ->success()
                            ->title('Import selesai')
                            ->body("Updated: {$updated}, Missing fid: {$missing}, Skipped: {$skipped}")
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
