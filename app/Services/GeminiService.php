<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected string $apiKey;
    protected string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-embedding-001:embedContent';

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY');
    }

    /**
     * Generate vector embedding from text using Gemini API
     * 
     * @param string $text
     * @return array|null
     */
    public function generateEmbedding(string $text): ?array
    {
        // 1. Validasi Input
        if (empty($this->apiKey)) {
            Log::error('Gemini API Key is missing in .env');
            return null;
        }

        if (trim($text) === '') {
            return null;
        }

        try {
            // 2. Kirim Request ke Google
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}?key={$this->apiKey}", [
                        // 'model' => 'models/embedding-001', // Redundant if URL specifies model
                        'content' => [
                            'parts' => [
                                ['text' => $text]
                            ]
                        ],
                        'outputDimensionality' => 768,
                    ]);

            // 3. Cek Error dari Google
            if ($response->failed()) {
                Log::error('Gemini API Error: ' . $response->body());
                return null;
            }

            // 4. Ambil Data Vektor dari JSON Response
            $data = $response->json();

            if (!isset($data['embedding']['values'])) {
                Log::error('Gemini Response missing values: ' . json_encode($data));
                return null;
            }

            return $data['embedding']['values'];

        } catch (\Exception $e) {
            Log::error('Gemini Connection Error: ' . $e->getMessage());
            return null;
        }
    }
}
