<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SlaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nama_pekerjaan' => $this->nama_pekerjaan,
            'jabatan' => $this->jabatan,
            'durasi_jam' => $this->durasi_jam,
            'keterangan' => $this->keterangan,
            'created_at' => optional($this->created_at)->toISOString(),
            'updated_at' => optional($this->updated_at)->toISOString(),
        ];
    }
}
