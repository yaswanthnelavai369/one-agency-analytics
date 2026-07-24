<?php

return [
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    ],

    'microsoft' => [
        'client_id' => env('MICROSOFT_CLIENT_ID'),
        'client_secret' => env('MICROSOFT_CLIENT_SECRET'),
    ],

    'anthropic' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
        'model' => env('ANTHROPIC_MODEL', 'claude-sonnet-4-5'),
    ],

    'ai' => [
        // Platform-wide default; agencies will be able to override per-agency once
        // the AI-provider selector ships in Settings (see spec: OpenAI/Gemini/Claude).
        'default_provider' => env('AI_DEFAULT_PROVIDER', 'anthropic'),
    ],
];
