<?php

namespace App\AI;

use App\AI\Contracts\AIProviderInterface;
use InvalidArgumentException;

/**
 * To add another AI provider (OpenAI, Gemini — per the spec's AI-provider integrations):
 *   1. Create App\AI\{Name}Provider implementing AIProviderInterface
 *   2. Add one line below
 * AIChatService and the controller never change.
 */
class AIProviderManager
{
    /** @var array<string, class-string<AIProviderInterface>> */
    protected array $providers = [
        'anthropic' => AnthropicProvider::class,
        // 'openai' => OpenAIProvider::class,
        // 'gemini' => GeminiProvider::class,
    ];

    public function resolve(?string $providerKey = null): AIProviderInterface
    {
        $key = $providerKey ?? config('services.ai.default_provider', 'anthropic');

        if (! isset($this->providers[$key])) {
            throw new InvalidArgumentException("Unknown AI provider [{$key}].");
        }

        return app($this->providers[$key]);
    }
}
