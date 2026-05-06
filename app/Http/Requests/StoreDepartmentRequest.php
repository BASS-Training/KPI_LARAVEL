<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class StoreDepartmentRequest extends SanitizedFormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $deptId = $this->route('department')?->id;
        $tenantId = app()->bound('current_tenant_id')
            ? app('current_tenant_id')
            : $this->user()?->tenant_id;

        return [
            'nama'        => ['required', 'string', 'max:100'],
            'kode'        => [
                'required',
                'string',
                'max:20',
                Rule::unique('departments', 'kode')
                    ->ignore($deptId)
                    ->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'deskripsi'   => ['nullable', 'string'],
            'is_active'   => ['nullable', 'boolean'],
        ];
    }
}
