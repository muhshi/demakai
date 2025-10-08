<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class EmbeddingService
{
    /**
     * Generate embedding dari Ollama (bge-m3 default)
     */
    public function embedText(string $text): array
    {
        // Potong teks terlalu panjang agar tidak overload
        $text = mb_substr($text, 0, 2000, 'UTF-8');

        $resp = Http::timeout(30)->post(env('OLLAMA_BASE_URL') . '/api/embeddings', [
            'model'  => env('OLLAMA_MODEL', 'bge-m3'),
            'prompt' => $text,
        ]);

        if (!$resp->successful()) {
            throw new \RuntimeException('Embedding failed: ' . $resp->status() . ' ' . $resp->body());
        }

        $embedding = $resp->json('embedding') ?? [];

        // pastikan array float numerik
        if (is_string($embedding)) {
            $embedding = json_decode($embedding, true);
        }
        $embedding = array_map('floatval', (array) $embedding);

        // normalisasi L2 (penting untuk cosine similarity yang adil)
        return $this->normalize($embedding);
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
        if ($len === 0) return 0.0;

        $dot = 0.0; $na = 0.0; $nb = 0.0;
        for ($i = 0; $i < $len; $i++) {
            $dot += $a[$i] * $b[$i];
            $na  += $a[$i] ** 2;
            $nb  += $b[$i] ** 2;
        }

        $den = sqrt($na) * sqrt($nb);
        return $den > 0 ? $dot / $den : 0.0;
    }
}
