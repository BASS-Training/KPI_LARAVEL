<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KpiReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user' => $this->whenLoaded('user', fn () => new UserResource($this->user)),
            'kpi_component_id' => $this->kpi_component_id,
            'kpi_component' => $this->whenLoaded('kpiComponent', fn () => new KpiComponentResource($this->kpiComponent)),
            'period_type' => $this->period_type,
            'tanggal' => optional($this->tanggal)->toDateString(),
            'period_label' => $this->period_label,
            'nilai_target' => $this->nilai_target !== null ? (float) $this->nilai_target : null,
            'nilai_aktual' => $this->nilai_aktual !== null ? (float) $this->nilai_aktual : null,
            'persentase' => $this->persentase !== null ? (float) $this->persentase : null,
            'score_label' => $this->score_label,
            'catatan' => $this->catatan,
            'file_evidence_url' => $this->file_evidence_url,
            'status' => $this->status,
            'submitted_by' => $this->submitted_by,
            'submitted_at' => optional($this->submitted_at)->toISOString(),
            'created_at' => optional($this->created_at)->toISOString(),
            'updated_at' => optional($this->updated_at)->toISOString(),
        ];
    }
}
