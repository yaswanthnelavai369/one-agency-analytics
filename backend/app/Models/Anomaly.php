<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Anomaly extends Model
{
    protected $fillable = [
        'uuid', 'agency_id', 'client_id', 'integration_id', 'type', 'severity', 'metric',
        'current_value', 'baseline_value', 'change_percent', 'message',
        'possible_causes', 'recommended_fixes', 'status',
        'acknowledged_by', 'acknowledged_at', 'resolved_at', 'detected_date',
    ];

    protected function casts(): array
    {
        return [
            'possible_causes' => 'array',
            'recommended_fixes' => 'array',
            'acknowledged_at' => 'datetime',
            'resolved_at' => 'datetime',
            'detected_date' => 'date',
            'current_value' => 'float',
            'baseline_value' => 'float',
            'change_percent' => 'float',
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

    public function acknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }
}
