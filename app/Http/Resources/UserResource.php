<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
            'department_id' => $this->department_id,
            'department' => $this->whenLoaded('department', fn () => $this->department?->only(['id', 'nama', 'kode'])),
            'position_id' => $this->position_id,
            'role_ref' => $this->whenLoaded('positionRef', fn () => $this->positionRef ? [
                'id' => $this->positionRef->id,
                'name' => $this->positionRef->nama,
                'slug' => $this->positionRef->kode,
            ] : null),
            'status_karyawan' => $this->status_karyawan,
            'tanggal_masuk' => optional($this->tanggal_masuk)->toDateString(),
            'no_hp' => $this->no_hp,
            'email' => $this->email,
            'role' => $this->role,
            'spatie_roles' => $this->whenLoaded('roles', fn () => $this->roles->pluck('name')),
            'created_at' => optional($this->created_at)->toISOString(),
            'updated_at' => optional($this->updated_at)->toISOString(),
        ];
    }
}
