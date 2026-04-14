<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KpiIndicatorBreakdownResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'type' => $this['type'] ?? 'indicator',
            'indicator_id' => $this['indicator_id'] ?? null,
            'task_id' => $this['task_id'] ?? null,
            'name' => $this['name'] ?? null,
            'description' => $this['description'] ?? null,
            'weight' => $this['weight'] ?? null,
            'target_value' => $this['target_value'] ?? null,
            'actual_value' => $this['actual_value'] ?? null,
            'achievement_ratio' => $this['achievement_ratio'] ?? null,
            'score' => $this['score'] ?? null,
            'status' => $this['status'] ?? null,
            'status_label' => $this['status_label'] ?? null,
            'period' => $this['period'] ?? null,
            'start_date' => $this['start_date'] ?? null,
            'end_date' => $this['end_date'] ?? null,
        ];
    }
}
