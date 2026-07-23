<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Integration extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid', 'agency_id', 'client_id', 'provider', 'display_name', 'external_account_id',
        'status', 'last_error', 'connected_by', 'connected_at', 'last_synced_at',
        'sync_frequency', 'config',
    ];

    protected function casts(): array
    {
        return [
            'connected_at' => 'datetime',
            'last_synced_at' => 'datetime',
            'config' => 'array',
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

    public function connector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'connected_by');
    }

    public function oauthToken(): HasOne
    {
        return $this->hasOne(OauthToken::class);
    }

    public function metrics(): HasMany
    {
        return $this->hasMany(AnalyticsMetric::class);
    }

    public function isConnected(): bool
    {
        return $this->status === 'connected';
    }
}
