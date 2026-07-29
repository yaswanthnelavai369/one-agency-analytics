<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatThreadResource extends JsonResource
{
    /** @param string $side 'agency' | 'client' — determines the unread count perspective */
    public function __construct($resource, protected string $side)
    {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'unread_count' => $this->unreadCountFor($this->side),
            'messages' => $this->whenLoaded('messages', fn () => $this->messages->map(fn ($m) => [
                'id' => $m->id,
                'sender_side' => $m->sender_side,
                'sender_name' => $m->sender?->name,
                'content' => $m->content,
                'created_at' => $m->created_at,
            ])),
            'last_message_at' => $this->last_message_at,
        ];
    }
}
