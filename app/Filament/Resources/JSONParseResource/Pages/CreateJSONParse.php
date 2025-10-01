<?php

namespace App\Filament\Resources\JSONParseResource\Pages;

use App\Filament\Resources\JSONParseResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Services\TikaService;
use App\Services\EmbeddingService;
use Filament\Notifications\Notification;

class CreateJSONParse extends CreateRecord
{
    protected static string $resource = JSONParseResource::class;

    public ?string $preview = null;
    public ?int $totalHalaman = null;

    /** Tombol Parse → hasil HTML untuk user */
    public function previewParse(): void
    {
        try {
            $data = $this->form->getState();

            if (empty($data['document'])) {
                Notification::make()
                    ->title('Dokumen belum diupload')
                    ->warning()
                    ->send();
                return;
            }

            if (!empty($this->preview)) {
                Notification::make()
                    ->title('Dokumen sudah diparse')
                    ->body('Gunakan tombol Preview Parsing untuk melihat hasilnya.')
                    ->info()
                    ->send();
                return;
            }

            $filePath = Storage::disk('local')->path($data['document']);
            $tika = app(TikaService::class);

            // metadata (jumlah halaman)
            $meta = $tika->getMetadata($filePath);
            $this->totalHalaman = $meta['xmpTPg:NPages'] ?? null;

            // HTML untuk preview
            $parsedHtml = $tika->parseFileAsHtml($filePath);
            $parsedHtml = preg_replace('/<p>\s*<\/p>/i', '', $parsedHtml);

            $this->preview = empty(trim(strip_tags($parsedHtml)))
                ? "<p><em>[Kosong] Tidak ada teks terdeteksi. Mungkin file hasil scan/gambar.</em></p>"
                : $parsedHtml;

            Notification::make()
                ->title('Parsing berhasil')
                ->body('Silakan klik Preview Parsing untuk melihat hasilnya.')
                ->success()
                ->send();

        } catch (\Throwable $e) {
            Notification::make()
                ->title('Parsing gagal')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    /** Saat simpan → plain text untuk embedding + metadata lengkap */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        try {
            if (empty($data['title']) || empty($data['source_type'])) {
                Notification::make()
                    ->title('Gagal menyimpan')
                    ->body('Judul dan Sumber Data wajib diisi sebelum menyimpan dokumen.')
                    ->danger()
                    ->send();

                throw \Illuminate\Validation\ValidationException::withMessages([
                    'title'       => 'Judul wajib diisi.',
                    'source_type' => 'Sumber Data wajib diisi.',
                ]);
            }

            Notification::make()
                ->title('Sedang memproses embedding...')
                ->body('Proses ini mungkin memakan waktu.')
                ->info()
                ->send();

            $filePath = Storage::disk('local')->path($data['document']);
            $tika     = app(TikaService::class);

            // metadata dari Tika
            $meta = $tika->getMetadata($filePath);

            // plain text → embedding
            $parsedText = $tika->parseFile($filePath);

            // chunking
            $chunks = $this->chunkText($parsedText, 800);

            // embedding tiap chunk
            $embed = app(EmbeddingService::class);
            $chunkData = [];
            foreach ($chunks as $i => $chunk) {
                try {
                    $embedding = $embed->embedText($chunk);

                    $chunkData[] = [
                        'chunk_id'  => $i,
                        'text'      => $chunk,
                        'embedding' => $embedding,
                    ];
                } catch (\Throwable $ex) {
                    // skip kalau ada error di satu chunk
                    $chunkData[] = [
                        'chunk_id'  => $i,
                        'text'      => $chunk,
                        'embedding' => null,
                        'error'     => $ex->getMessage(),
                    ];
                }
            }

            // similarity sederhana
            $sim = 0;
            try {
                $fullEmbedding = $embed->embedText($parsedText);
                $totalSim = 0;
                foreach ($chunks as $chunk) {
                    $chunkEmb = $embed->embedText($chunk);
                    $totalSim += $embed->cosineSimilarity($fullEmbedding, $chunkEmb);
                }
                $sim = $totalSim / max(1, count($chunks));
            } catch (\Throwable $e) {
                $sim = 0;
            }

            // pastikan tags array
            $data['tags'] = array_values((array)($data['tags'] ?? []));

            // isi semua kolom fillable
            $data['filename']         = basename($filePath);
            $data['chunks']           = $chunkData;
            $data['similarity_score'] = $sim;
            $data['metadata'] = [
                'num_pages'    => $meta['xmpTPg:NPages'] ?? null,
                'chars_count'  => mb_strlen($parsedText ?? '', 'UTF-8'),
                'num_chunks'   => count($chunks),
                'content_type' => $meta['Content-Type'] ?? null,
                'producer'     => $meta['pdf:producer'] ?? null,
                'pdf_version'  => $meta['pdf:PDFVersion'] ?? null,
                'tika_version' => $meta['X-Parsed-By'] ?? null,
                'title_from_pdf' => $meta['dc:title'] ?? null,
            ];
            $data['uploaded_by'] = Auth::id();
            $data['uploaded_at'] = now();

            unset($data['document']); // jangan simpan path upload mentah

            Notification::make()
                ->title('Dokumen berhasil diproses & disimpan')
                ->success()
                ->send();

            return $data;

        } catch (\Throwable $e) {
            Notification::make()
                ->title('Gagal menyimpan dokumen')
                ->body($e->getMessage())
                ->danger()
                ->send();

            throw $e; // biar tetap fail di Laravel, tapi user sudah dapat notif
        }
    }

    protected function getRedirectUrl(): string
    {
        return \App\Filament\Resources\DocumentResource::getUrl('index');
    }

    private function chunkText(string $text, int $maxChars = 800): array
    {
        $words = preg_split('/\s+/', $text ?? '', -1, PREG_SPLIT_NO_EMPTY);
        $chunks = []; $buf = '';
        foreach ($words as $w) {
            if (mb_strlen($buf . ' ' . $w, 'UTF-8') > $maxChars) {
                if ($buf !== '') $chunks[] = trim($buf);
                $buf = $w;
            } else {
                $buf = $buf === '' ? $w : $buf . ' ' . $w;
            }
        }
        if ($buf !== '') $chunks[] = trim($buf);
        return $chunks ?: [''];
    }
}
