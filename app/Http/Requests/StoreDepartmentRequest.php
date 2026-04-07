<?php

namespace App\Http\Requests;

class StoreDepartmentRequest extends SanitizedFormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $deptId = $this->route('department')?->id;

        return [
            'nama'        => ['required', 'string', 'max:100'],
            'kode'        => ['required', 'string', 'max:20', "unique:departments,kode,{$deptId}"],
            'division_id' => ['nullable', 'exists:divisions,id'],
            'deskripsi'   => ['nullable', 'string'],
            'is_active'   => ['nullable', 'boolean'],
        ];
    }
}
