<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OauthToken extends Model
{
    protected $fillable = [
        'integration_id', 'access_token_encrypted', 'refresh_token_encrypted', 'expires_at', 'scopes',
    ];

    protected $hidden = ['access_token_encrypted', 'refresh_token_encrypted'];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'scopes' => 'array',
        ];
    }

    public function integration(): BelongsTo
    {
        return $this->belongsTo(Integration::class);
    }
}
