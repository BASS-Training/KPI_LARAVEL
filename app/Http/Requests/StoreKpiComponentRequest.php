<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class StoreKpiComponentRequest extends SanitizedFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'jabatan' => ['required', 'string', 'max:255'],
            'division_id' => ['nullable', 'exists:divisions,id'],
            'position_id' => ['nullable', 'exists:positions,id'],
            'objectives' => ['required', 'string', 'max:255'],
            'strategy' => ['required', 'string'],
            'bobot' => ['required', 'numeric', 'min:0', 'max:1'],
            'target' => ['nullable', 'numeric'],
            'satuan' => ['nullable', 'string', 'max:50'],
            'tipe' => ['required', Rule::in(['zero_delay', 'zero_error', 'zero_complaint', 'achievement', 'csi'])],
            'kpi_type' => ['nullable', Rule::in(['number', 'percentage', 'boolean'])],
            'period' => ['nullable', Rule::in(['daily', 'weekly', 'monthly'])],
            'catatan' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
