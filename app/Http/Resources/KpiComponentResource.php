<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KpiComponentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'jabatan' => $this->jabatan,
            'objectives' => $this->objectives,
            'strategy' => $this->strategy,
            'bobot' => (float) $this->bobot,
            'target' => $this->target !== null ? (float) $this->target : null,
            'tipe' => $this->tipe,
            'catatan' => $this->catatan,
            'is_active' => (bool) $this->is_active,
            'created_at' => optional($this->created_at)->toISOString(),
            'updated_at' => optional($this->updated_at)->toISOString(),
        ];
    }
}
