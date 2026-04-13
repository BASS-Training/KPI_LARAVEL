<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KpiScore extends Model
{
    protected $fillable = [
        'user_id',
        'role_id',
        'period_type',
        'period_start',
        'period_end',
        'raw_score',
        'normalized_score',
        'status',
        'grade',
        'breakdown',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'raw_score' => 'decimal:2',
            'normalized_score' => 'decimal:2',
            'breakdown' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }
}
