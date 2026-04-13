<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KpiIndicator extends Model
{
    protected $fillable = [
        'name',
        'description',
        'weight',
        'default_target_value',
        'role_id',
    ];

    protected function casts(): array
    {
        return [
            'weight' => 'decimal:2',
            'default_target_value' => 'decimal:2',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function targets(): HasMany
    {
        return $this->hasMany(KpiTarget::class, 'indicator_id');
    }

    public function records(): HasMany
    {
        return $this->hasMany(KpiRecord::class, 'indicator_id');
    }
}
