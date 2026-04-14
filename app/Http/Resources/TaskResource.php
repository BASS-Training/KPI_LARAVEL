<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'task_type' => $this->task_type,
            'title' => $this->judul,
            'description' => $this->deskripsi,
            'tanggal' => optional($this->tanggal)->toDateString(),
            'start_date' => optional($this->start_date)->toDateString(),
            'end_date' => optional($this->end_date)->toDateString(),
            'judul' => $this->judul,
            'jenis_pekerjaan' => $this->jenis_pekerjaan,
            'status' => $this->status,
            'status_code' => $this->status_code,
            'status_label' => $this->status_label,
            'waktu_mulai' => $this->waktu_mulai,
            'waktu_selesai' => $this->waktu_selesai,
            'ada_delay' => (bool) $this->ada_delay,
            'ada_error' => (bool) $this->ada_error,
            'ada_komplain' => (bool) $this->ada_komplain,
            'deskripsi' => $this->deskripsi,
            'assigned_by' => $this->assigned_by,
            'assigned_to' => $this->assigned_to_user_id,
            'weight' => $this->weight !== null ? (float) $this->weight : null,
            'target_value' => $this->target_value !== null ? (float) $this->target_value : null,
            'actual_value' => $this->actual_value !== null ? (float) $this->actual_value : null,
            'manual_score' => $this->manual_score !== null ? (float) $this->manual_score : null,
            'mapped_at' => optional($this->mapped_at)->toISOString(),
            'user' => new UserResource($this->whenLoaded('user')),
            'assignee' => new UserResource($this->whenLoaded('assignee')),
            'assigner' => new UserResource($this->whenLoaded('assigner')),
            'kpi_component' => new KpiComponentResource($this->whenLoaded('kpiComponent')),
            'mapped_by_user' => new UserResource($this->whenLoaded('mapper')),
            'task_scores' => $this->whenLoaded('taskScores', fn () => $this->taskScores->map(fn ($score) => [
                'id' => $score->id,
                'score' => (float) $score->score,
                'period' => $score->period,
            ])->values()),
            'created_at' => optional($this->created_at)->toISOString(),
            'updated_at' => optional($this->updated_at)->toISOString(),
        ];
    }
}
