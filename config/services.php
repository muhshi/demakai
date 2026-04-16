<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'tika' => [
        'base_url' => env('TIKA_BASE_URL', 'http://localhost:9999'),
    ],

    'ollama' => [
        'base_url' => env('OLLAMA_BASE_URL', 'http://127.0.0.1:11434'),
        'model' => env('OLLAMA_MODEL', 'bge-m3'),
    ],

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
    ],

    // ── Python Search Microservice ────────────────────────────────────────────
    // Aktifkan dengan PYTHON_SEARCH_ENABLED=true di .env
    // Jalankan server dulu: uvicorn api:app --host 127.0.0.1 --port 8000
    'python_search' => [
        'enabled' => env('PYTHON_SEARCH_ENABLED', false),
        'url' => env('PYTHON_SEARCH_URL', 'http://127.0.0.1:8000'),
        'method' => env('PYTHON_SEARCH_METHOD', 'sql'),      // sql | hybrid
        'processing' => env('PYTHON_SEARCH_PROCESSING', 'none'), // none | basic | advanced
    ],

];
