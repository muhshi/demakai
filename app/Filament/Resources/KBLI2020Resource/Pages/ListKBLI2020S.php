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
            Actions\Action::make('importContoh')
                ->label('Import Contoh (Excel/CSV)')
                ->icon('heroicon-o-arrow-up-tray')
                ->form([
                    FileUpload::make('file')
                        ->label('File Excel/CSV')
                        ->required()
                        ->disk('public')               // eksplisit pakai public
                        ->directory('imports')         // simpan sementara
                        ->acceptedFileTypes([
                            'text/csv',
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        ]),
                    Toggle::make('append')
                        ->label('Tambah (append) ke contoh_lapangan yang sudah ada?')
                        ->default(true),
                    TextInput::make('delimiter')
                        ->label('Pemisah multi-contoh di sel')
                        ->helperText('Jika satu sel berisi banyak contoh, pisahkan dengan tanda ini. Mis: ;')
                        ->default(';')
                        ->maxLength(2),
                ])
                ->action(function (array $data) {
                    try {
                        // Path file di storage/public
                        $path = Storage::disk('public')->path($data['file']);

                        $spreadsheet = IOFactory::load($path);
                        $sheet = $spreadsheet->getActiveSheet();
                        $rows = $sheet->toArray(null, true, true, true);

                        $delimiter = $data['delimiter'] ?: ';';
                        $append = (bool) ($data['append'] ?? true);

                        $updated = 0;
                        $skipped = 0;
                        $missing = 0;

                        foreach ($rows as $idx => $row) {
                            if ($idx === 1) {
                                // lewati header (baris 1)
                                continue;
                            }

                            $kode = isset($row['A']) ? trim((string) $row['A']) : '';
                            $contohCell = isset($row['B']) ? trim((string) $row['B']) : '';

                            if ($kode === '') {
                                $skipped++;
                                continue;
                            }

                            // Normalisasi: pastikan 5 digit (misal 1111 -> 01111)
                            if (ctype_digit($kode) && strlen($kode) < 5) {
                                $kode = str_pad($kode, 5, '0', STR_PAD_LEFT);
                            }

                            // Pecah multiple contoh di satu sel
                            $contohList = [];
                            if ($contohCell !== '') {
                                $parts = array_map('trim', explode($delimiter, $contohCell));
                                $contohList = array_values(array_filter($parts, fn($v) => $v !== ''));
                            }

                            // Cari dokumen KBLI
                            $doc = KBLI2020::where('kode_5_digit', $kode)->first();
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
