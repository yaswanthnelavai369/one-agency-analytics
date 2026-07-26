<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Goal extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid', 'agency_id', 'client_id', 'created_by', 'name', 'metric', 'tracking_mode',
        'target_value', 'current_value', 'format', 'start_date', 'deadline', 'status', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'target_value' => 'float',
            'current_value' => 'float',
            'start_date' => 'date',
            'deadline' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function progress(): HasMany
    {
        return $this->hasMany(GoalProgress::class)->orderBy('date');
    }

    public function isAutoTracked(): bool
    {
        return $this->tracking_mode !== 'manual' && $this->metric !== null;
    }
}
