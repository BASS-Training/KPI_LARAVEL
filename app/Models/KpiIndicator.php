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
        'formula',
        'role_id',
        'department_id',
    ];

    protected function casts(): array
    {
        return [
            'weight'               => 'decimal:2',
            'default_target_value' => 'decimal:2',
            'formula'              => 'array',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function targets(): HasMany
    {
        return $this->hasMany(KpiTarget::class, 'indicator_id');
    }

    public function records(): HasMany
    {
        return $this->hasMany(KpiRecord::class, 'indicator_id');
    }

    /** Human-readable formula type label. */
    public function getFormulaTtypeLabel(): string
    {
        return match ($this->formula['type'] ?? 'percentage') {
            'conditional'  => 'Kondisional',
            'threshold'    => 'Bertahap',
            'zero_penalty' => 'Zero Penalty',
            'flat'         => 'Tetap',
            default        => 'Persentase',
        };
    }
}
