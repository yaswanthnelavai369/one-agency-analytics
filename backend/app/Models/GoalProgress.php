<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoalProgress extends Model
{
    protected $fillable = ['goal_id', 'date', 'value', 'source', 'logged_by'];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'value' => 'float',
        ];
    }

    public function goal(): BelongsTo
    {
        return $this->belongsTo(Goal::class);
    }
}
