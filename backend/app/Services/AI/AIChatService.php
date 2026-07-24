<?php

namespace App\Services\AI;

use App\AI\AIContextBuilder;
use App\AI\AIProviderManager;
use App\Models\AIConversation;
use App\Models\AIMessage;
use App\Models\AIUsageLog;
use App\Models\Client;
use App\Models\User;
use App\Repositories\Contracts\AIConversationRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AIChatService
{
    public function __construct(
        protected AIProviderManager $providers,
        protected AIContextBuilder $contextBuilder,
        protected AIConversationRepositoryInterface $conversations,
    ) {}

    public function getOrCreateConversation(User $user, Client $client): AIConversation
    {
        $existing = $this->conversations->forUser($user->agency_id, $user->id, $client->id)->first();

        if ($existing) {
            return $existing->load('messages');
        }

        return $this->conversations->create([
            'uuid' => Str::uuid(),
            'agency_id' => $user->agency_id,
            'client_id' => $client->id,
            'user_id' => $user->id,
            'title' => "{$client->name} — AI Assistant",
        ])->load('messages');
    }

    /** Sends one user message, gets the assistant's reply, and persists both turns + a usage log entry. */
    public function sendMessage(AIConversation $conversation, Client $client, string $userMessage): AIMessage
    {
        $this->assertWithinPlanLimit($client->agency);

        return DB::transaction(function () use ($conversation, $client, $userMessage) {
            AIMessage::create([
                'conversation_id' => $conversation->id,
                'role' => 'user',
                'content' => $userMessage,
            ]);

            $systemPrompt = $this->contextBuilder->buildSystemPrompt($client);
            $history = $conversation->messages()
                ->where('role', '!=', 'system')
                ->get(['role', 'content'])
                ->map(fn ($m) => ['role' => $m->role, 'content' => $m->content])
                ->all();

            $provider = $this->providers->resolve();
            $result = $provider->complete($systemPrompt, $history);

            $assistantMessage = AIMessage::create([
                'conversation_id' => $conversation->id,
                'role' => 'assistant',
                'content' => $result['content'],
                'tokens_used' => ($result['input_tokens'] ?? 0) + ($result['output_tokens'] ?? 0),
            ]);

            AIUsageLog::create([
                'agency_id' => $client->agency_id,
                'client_id' => $client->id,
                'user_id' => $conversation->user_id,
                'provider' => $provider->key(),
                'model' => $result['model'],
                'credits_used' => 1,
                'input_tokens' => $result['input_tokens'],
                'output_tokens' => $result['output_tokens'],
            ]);

            $conversation->touch();

            return $assistantMessage;
        });
    }

    protected function assertWithinPlanLimit($agency): void
    {
        $limit = $agency->plan?->ai_credit_limit_monthly;

        if (is_null($limit)) {
            return;
        }

        $used = AIUsageLog::where('agency_id', $agency->id)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('credits_used');

        if ($used >= $limit) {
            throw ValidationException::withMessages([
                'plan' => "Your plan includes {$limit} AI credits per month, and you've used them all. Upgrade for more.",
            ]);
        }
    }
}
