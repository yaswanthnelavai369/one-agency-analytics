<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DashboardLayout extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid', 'agency_id', 'client_id', 'user_id', 'name',
        'is_default', 'is_shared', 'is_template', 'template_scope',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_shared' => 'boolean',
            'is_template' => 'boolean',
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

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function widgets(): HasMany
    {
        return $this->hasMany(DashboardWidget::class)->orderBy('sort_order');
    }
}
