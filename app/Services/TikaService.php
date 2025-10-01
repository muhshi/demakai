<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class TikaService
{
    private string $url;

    public function __construct()
    {
        // Sesuaikan dengan port Tika server kamu (default 9999)
        $this->url = config('services.tika.url', 'http://localhost:9999');
    }

    /**
     * Parse file jadi plain text
     */
    public function parseFile(string $filePath): string
    {
        $resp = Http::withHeaders(['Accept' => 'text/plain'])
            ->withBody(file_get_contents($filePath), 'application/pdf')
            ->put("{$this->url}/tika");

        if (!$resp->successful()) {
            throw new \RuntimeException('Tika parse failed: '.$resp->status().' '.$resp->body());
        }

        return $resp->body() ?? '';
    }

    /**
     * Parse file jadi HTML
     */
    public function parseFileAsHtml(string $filePath): string
    {
        $resp = Http::withBody(file_get_contents($filePath), 'application/pdf')
            ->accept('text/html')
            ->put("{$this->url}/tika");

        if ($resp->failed()) {
            throw new \Exception('Tika HTML parse failed: '.$resp->body());
        }

        return $resp->body();
    }

    public function getMetadata(string $filePath): array
    {
        $resp = Http::withBody(file_get_contents($filePath), 'application/pdf')
            ->accept('application/json')
            ->put("{$this->url}/meta");

        if (!$resp->successful()) {
            throw new \RuntimeException('Tika metadata failed: '.$resp->status().' '.$resp->body());
        }

        return $resp->json();
    }
}
