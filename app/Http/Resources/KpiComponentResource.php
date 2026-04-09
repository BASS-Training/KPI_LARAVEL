<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KpiComponentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'jabatan'       => $this->jabatan,
            'division_id'   => $this->division_id,
            'division'      => $this->whenLoaded('division', fn () => [
                'id'   => $this->division->id,
                'nama' => $this->division->nama,
                'kode' => $this->division->kode,
            ]),
            'department_id' => $this->department_id,
            'department'    => $this->whenLoaded('department', fn () => [
                'id'          => $this->department->id,
                'nama'        => $this->department->nama,
                'division_id' => $this->department->division_id,
            ]),
            'position_id'   => $this->position_id,
            'objectives'    => $this->objectives,
            'strategy'      => $this->strategy,
            'bobot'         => (float) $this->bobot,
            'target'        => $this->target !== null ? (float) $this->target : null,
            'satuan'        => $this->satuan,
            'tipe'          => $this->tipe,
            'kpi_type'      => $this->kpi_type,
            'period'        => $this->period,
            'catatan'       => $this->catatan,
            'is_active'     => (bool) $this->is_active,
            'created_at'    => optional($this->created_at)->toISOString(),
            'updated_at'    => optional($this->updated_at)->toISOString(),
        ];
    }
}
