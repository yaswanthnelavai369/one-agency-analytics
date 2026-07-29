<?php

namespace App\Services\Chat;

use App\Models\ChatMessage;
use App\Models\ChatThread;
use App\Models\Client;
use App\Models\User;
use App\Repositories\Contracts\ChatRepositoryInterface;
use Illuminate\Support\Str;

class ChatService
{
    public function __construct(protected ChatRepositoryInterface $threads) {}

    public function getOrCreateThread(Client $client): ChatThread
    {
        $existing = $this->threads->findByClient($client->id);

        if ($existing) {
            return $existing;
        }

        return $this->threads->create([
            'uuid' => Str::uuid(),
            'agency_id' => $client->agency_id,
            'client_id' => $client->id,
        ])->load('messages.sender');
    }

    public function sendMessage(ChatThread $thread, User $sender, string $side, string $content): ChatMessage
    {
        $message = ChatMessage::create([
            'thread_id' => $thread->id,
            'sender_id' => $sender->id,
            'sender_side' => $side,
            'content' => $content,
        ]);

        $thread->forceFill(['last_message_at' => now()])->save();

        // Sending implicitly marks your own side as caught up.
        $this->markRead($thread, $side);

        return $message;
    }

    public function markRead(ChatThread $thread, string $side): void
    {
        $thread->forceFill([
            $side === 'agency' ? 'agency_last_read_at' : 'client_last_read_at' => now(),
        ])->save();
    }
}
