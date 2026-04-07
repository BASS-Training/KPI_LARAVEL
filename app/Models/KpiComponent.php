<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KpiComponent extends Model
{
    protected $fillable = [
        'jabatan',
        'objectives',
        'strategy',
        'bobot',
        'target',
        'tipe',
        'catatan',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'bobot' => 'decimal:2',
            'target' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function getObjectiveAttribute(): string
    {
        return $this->objectives;
    }

    public function getWeightAttribute(): float
    {
        return (float) $this->bobot;
    }

    public function getTypeAttribute(): string
    {
        return $this->tipe;
    }

    public function getNoteAttribute(): ?string
    {
        return $this->catatan;
    }

    public function getPositionAttribute(): string
    {
        return $this->jabatan;
    }
}
