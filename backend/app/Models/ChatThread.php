<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatThread extends Model
{
    protected $fillable = ['uuid', 'agency_id', 'client_id', 'agency_last_read_at', 'client_last_read_at', 'last_message_at'];

    protected function casts(): array
    {
        return [
            'agency_last_read_at' => 'datetime',
            'client_last_read_at' => 'datetime',
            'last_message_at' => 'datetime',
        ];
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'thread_id')->orderBy('created_at');
    }

    /** Unread count for the given side, based on the other side's messages since this side last read. */
    public function unreadCountFor(string $side): int
    {
        $lastRead = $side === 'agency' ? $this->agency_last_read_at : $this->client_last_read_at;
        $otherSide = $side === 'agency' ? 'client' : 'agency';

        return $this->messages()
            ->where('sender_side', $otherSide)
            ->when($lastRead, fn ($q) => $q->where('created_at', '>', $lastRead))
            ->count();
    }
}
