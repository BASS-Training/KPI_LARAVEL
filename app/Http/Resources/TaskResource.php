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
            'tanggal' => optional($this->tanggal)->toDateString(),
            'judul' => $this->judul,
            'jenis_pekerjaan' => $this->jenis_pekerjaan,
            'status' => $this->status,
            'waktu_mulai' => $this->waktu_mulai,
            'waktu_selesai' => $this->waktu_selesai,
            'ada_delay' => (bool) $this->ada_delay,
            'ada_error' => (bool) $this->ada_error,
            'ada_komplain' => (bool) $this->ada_komplain,
            'deskripsi' => $this->deskripsi,
            'manual_score' => $this->manual_score !== null ? (float) $this->manual_score : null,
            'mapped_at' => optional($this->mapped_at)->toISOString(),
            'user' => new UserResource($this->whenLoaded('user')),
            'kpi_component' => new KpiComponentResource($this->whenLoaded('kpiComponent')),
            'mapped_by_user' => new UserResource($this->whenLoaded('mapper')),
            'created_at' => optional($this->created_at)->toISOString(),
            'updated_at' => optional($this->updated_at)->toISOString(),
        ];
    }
}
