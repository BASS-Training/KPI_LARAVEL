<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Division extends Model
{
    protected $fillable = [
        'nama',
        'kode',
        'deskripsi',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function kpiComponents(): HasMany
    {
        return $this->hasMany(KpiComponent::class);
    }

    public function kpiReports(): HasMany
    {
        return $this->hasManyThrough(KpiReport::class, KpiComponent::class);
    }
}
