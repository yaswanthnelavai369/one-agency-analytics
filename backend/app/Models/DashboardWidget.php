<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DashboardWidget extends Model
{
    protected $fillable = [
        'uuid', 'dashboard_layout_id', 'widget_type', 'config',
        'pos_x', 'pos_y', 'width', 'height', 'is_hidden', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'config' => 'array',
            'is_hidden' => 'boolean',
        ];
    }

    public function layout(): BelongsTo
    {
        return $this->belongsTo(DashboardLayout::class, 'dashboard_layout_id');
    }
}
