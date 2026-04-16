<?php

namespace App\Services;

use RuntimeException;
use Illuminate\Support\Facades\Http;

class EmbeddingService
{
    /**
     * Generate embedding dari Ollama (bge-m3 default)
     */
    /**
     * Generate embedding dari Gemini (gemini-embedding-001)
     */
    public function embedText(string $text): array
    {
        // Potong teks terlalu panjang agar tidak overload (Gemini limit 2048 token, aman di 8000 char)
        $text = mb_substr($text, 0, 8000, 'UTF-8');
        $apiKey = config('services.gemini.api_key');

        if (empty($apiKey)) {
            throw new RuntimeException('GEMINI_API_KEY is missing in .env');
        }

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-embedding-001:embedContent?key={$apiKey}", [
                    'model' => 'models/gemini-embedding-001',
                    'content' => [
                        'parts' => [
                            ['text' => $text]
                        ]
                    ],
                    'outputDimensionality' => 768,
                ]);

        if ($response->failed()) {
            throw new RuntimeException('Gemini API Error: ' . $response->body());
        }

        $embedding = $response->json('embedding.values');

        if (empty($embedding)) {
            throw new RuntimeException('No embedding returned from Gemini API');
        }

        return $embedding; // Gemini embeddings are already normalized usually, but can re-normalize if needed
    }

    /**
     * Normalisasi vektor ke unit length (L2 normalization)
     */
    private function normalize(array $vec): array
    {
        $norm = sqrt(array_sum(array_map(fn($x) => $x * $x, $vec)));
        return $norm > 0 ? array_map(fn($x) => $x / $norm, $vec) : $vec;
    }

    /**
     * Hitung cosine similarity antar dua vektor
     */
    public function cosineSimilarity(array $a, array $b): float
    {
        $len = min(count($a), count($b));
        if ($len === 0)
            return 0.0;

        $dot = 0.0;
        $na = 0.0;
        $nb = 0.0;
        for ($i = 0; $i < $len; $i++) {
            $dot += $a[$i] * $b[$i];
            $na += $a[$i] ** 2;
            $nb += $b[$i] ** 2;
        }

        $den = sqrt($na) * sqrt($nb);
        return $den > 0 ? $dot / $den : 0.0;
    }
}
