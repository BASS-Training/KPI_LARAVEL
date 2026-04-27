<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    protected $fillable = [
        'tenant_code',
        'tenant_name',
        'domain',
        'status',
        'logo_url',
        'primary_color',
        'contact_email',
        'contact_phone',
        'address',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function secondaryUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_tenants')
                    ->withPivot('role', 'is_primary')
                    ->withTimestamps();
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function positions(): HasMany
    {
        return $this->hasMany(Position::class);
    }

    public function kpiTemplates(): HasMany
    {
        return $this->hasMany(KpiTemplate::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
