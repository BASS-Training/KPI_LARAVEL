<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\DivisionResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nip' => $this->nip,
            'nama' => $this->nama,
            'jabatan' => $this->jabatan,
            'departemen' => $this->departemen,
            'division_id' => $this->division_id,
            'division' => $this->whenLoaded('division', fn () => new DivisionResource($this->division)),
            'department_id' => $this->department_id,
            'position_id' => $this->position_id,
            'status_karyawan' => $this->status_karyawan,
            'tanggal_masuk' => optional($this->tanggal_masuk)->toDateString(),
            'no_hp' => $this->no_hp,
            'email' => $this->email,
            'role' => $this->role,
            'created_at' => optional($this->created_at)->toISOString(),
            'updated_at' => optional($this->updated_at)->toISOString(),
        ];
    }
}
