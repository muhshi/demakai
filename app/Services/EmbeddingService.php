<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class EmbeddingService
{
    public function embedText(string $text): array
    {
        if (trim($text) === '') {
            return [];
        }

        $resp = Http::timeout(60)->post(env('OLLAMA_BASE_URL') . '/api/embeddings', [
            'model'  => env('OLLAMA_MODEL', 'bge-m3'),
            'prompt' => mb_substr($text, 0, 4000), // batasi panjang biar aman
        ]);

        if (!$resp->successful()) {
            throw new \RuntimeException('Embedding failed: ' . $resp->status() . ' ' . $resp->body());
        }

        $data = $resp->json();

        // ✅ jika embedding sudah array numerik
        if (isset($data['embedding']) && is_array($data['embedding'])) {
            return array_map('floatval', $data['embedding']);
        }

        // ✅ jika embedding berupa string JSON
        if (isset($data['embedding']) && is_string($data['embedding'])) {
            $decoded = json_decode($data['embedding'], true);
            if (is_array($decoded)) {
                return array_map('floatval', $decoded);
            }
        }

        // fallback kosong
        return [];
    }

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
        return $den > 0 ? ($dot / $den) : 0.0;
    }
}
