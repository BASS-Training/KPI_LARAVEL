<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Position extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'nama', 'kode', 'department_id', 'level', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function kpiComponents(): HasMany
    {
        return $this->hasMany(KpiComponent::class);
    }

    public function slas(): HasMany
    {
        return $this->hasMany(Sla::class);
    }
}
