<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, HasRoles;

    protected $guard_name = 'sanctum';

    protected $fillable = [
        'uuid', 'agency_id', 'client_id', 'user_type', 'name', 'email', 'password',
        'avatar', 'phone', 'timezone', 'locale', 'theme_preference',
        'google_id', 'microsoft_id', 'status',
    ];

    protected $hidden = [
        'password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'last_login_at' => 'datetime',
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

    public function teamMembership(): HasMany
    {
        return $this->hasMany(TeamMember::class);
    }

    public function isMasterAdmin(): bool
    {
        return $this->user_type === 'master_admin';
    }

    public function isAgencyOwner(): bool
    {
        return $this->user_type === 'agency';
    }

    public function isClient(): bool
    {
        return $this->user_type === 'client';
    }

    public function isTeamMember(): bool
    {
        return $this->user_type === 'team_member';
    }

    public function hasTwoFactorEnabled(): bool
    {
        return ! is_null($this->two_factor_confirmed_at);
    }

    // Spatie roles/permissions are scoped by agency_id ("team"); this tells
    // the package which team the current user operates in when checks run
    // without an explicit team id being set on the PermissionRegistrar.
    public function getPermissionsTeamId(): ?int
    {
        return $this->agency_id;
    }
}
