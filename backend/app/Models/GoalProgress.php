<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoalProgress extends Model
{
    // Explicit: don't rely on Eloquent's pluralizer's judgment call on whether
    // 'progress' is an uncountable word (goal_progress vs goal_progresses) —
    // the migration created 'goal_progress', so pin it exactly.
    protected $table = 'goal_progress';

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
