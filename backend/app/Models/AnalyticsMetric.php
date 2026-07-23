<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalyticsMetric extends Model
{
    public $timestamps = true;

    protected $fillable = ['client_id', 'integration_id', 'metric', 'date', 'value', 'dimensions'];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'value' => 'float',
            'dimensions' => 'array',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function integration(): BelongsTo
    {
        return $this->belongsTo(Integration::class);
    }
}
