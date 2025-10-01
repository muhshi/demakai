<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class EmbeddingService
{
    public function embedText(string $text): array
    {
        $resp = Http::post(env('OLLAMA_BASE_URL').'/api/embeddings', [
            'model'  => env('OLLAMA_MODEL', 'nomic-embed-text'),
            'prompt' => $text,
        ]);


        if (!$resp->successful()) {
            throw new \RuntimeException('Embedding failed: '.$resp->status().' '.$resp->body());
        }

        return $resp->json('embedding') ?? [];
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
