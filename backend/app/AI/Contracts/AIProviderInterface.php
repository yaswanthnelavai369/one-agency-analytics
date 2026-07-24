<?php

namespace App\AI\Contracts;

/**
 * Every AI provider (Anthropic Claude, OpenAI, Gemini — per the spec's "AI Providers"
 * integration list) implements this. AIProviderManager resolves by key, same pattern
 * as IntegrationProviderInterface for marketing connectors.
 */
interface AIProviderInterface
{
    /** Key used in agencies' AI provider config, e.g. "anthropic". */
    public function key(): string;

    public function displayName(): string;

    /**
     * @param string $systemPrompt - grounding context: the client's real metrics/health score/
     *   recent activity, built by AIContextBuilder — never let the model answer from nothing.
     * @param array<array{role: string, content: string}> $messages - prior turns, oldest first
     * @return array{content: string, input_tokens: ?int, output_tokens: ?int, model: string}
     */
    public function complete(string $systemPrompt, array $messages): array;
}
