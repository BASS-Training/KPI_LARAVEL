<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KpiScoreDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'user' => new UserResource($this->whenLoaded('user', $this->user)),
            'role' => $this->user?->positionRef ? [
                'id' => $this->user->positionRef->id,
                'name' => $this->user->positionRef->nama,
                'slug' => $this->user->positionRef->kode,
            ] : null,
            'period_type' => $this->period_type,
            'period_start' => optional($this->period_start)->toDateString(),
            'period_end' => optional($this->period_end)->toDateString(),
            'raw_score' => (float) $this->raw_score,
            'normalized_score' => (float) $this->normalized_score,
            'status' => $this->status,
            'grade' => $this->grade,
            'rank' => $this->when(isset($this->rank), $this->rank),
            'breakdown' => KpiIndicatorBreakdownResource::collection(collect($this->breakdown ?? [])),
            'created_at' => optional($this->created_at)->toISOString(),
            'updated_at' => optional($this->updated_at)->toISOString(),
        ];
    }
}
