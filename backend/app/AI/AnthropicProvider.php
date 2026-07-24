<?php

namespace App\AI;

use App\AI\Contracts\AIProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class AnthropicProvider implements AIProviderInterface
{
    protected const API_URL = 'https://api.anthropic.com/v1/messages';
    protected const API_VERSION = '2023-06-01';

    public function key(): string
    {
        return 'anthropic';
    }

    public function displayName(): string
    {
        return 'Claude (Anthropic)';
    }

    public function complete(string $systemPrompt, array $messages): array
    {
        $apiKey = config('services.anthropic.api_key');
        $model = config('services.anthropic.model');

        if (! $apiKey) {
            throw new RuntimeException('Anthropic API key is not configured. Set ANTHROPIC_API_KEY in .env.');
        }

        $response = Http::withHeaders([
            'x-api-key' => $apiKey,
            'anthropic-version' => self::API_VERSION,
            'content-type' => 'application/json',
        ])->post(self::API_URL, [
            'model' => $model,
            'max_tokens' => 1024,
            'system' => $systemPrompt,
            'messages' => array_map(fn ($m) => ['role' => $m['role'], 'content' => $m['content']], $messages),
        ]);

        if ($response->failed()) {
            Log::error('Anthropic API call failed', ['body' => $response->body()]);
            throw new RuntimeException($response->json('error.message', 'The AI assistant is temporarily unavailable.'));
        }

        $body = $response->json();
        $text = collect($body['content'] ?? [])
            ->where('type', 'text')
            ->pluck('text')
            ->implode("\n");

        return [
            'content' => $text,
            'input_tokens' => $body['usage']['input_tokens'] ?? null,
            'output_tokens' => $body['usage']['output_tokens'] ?? null,
            'model' => $model,
        ];
    }
}
