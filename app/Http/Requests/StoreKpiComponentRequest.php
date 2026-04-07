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
            'objectives' => ['required', 'string', 'max:255'],
            'strategy' => ['required', 'string'],
            'bobot' => ['required', 'numeric', 'min:0', 'max:1'],
            'target' => ['nullable', 'numeric'],
            'tipe' => ['required', Rule::in(['zero_delay', 'zero_error', 'zero_complaint', 'achievement', 'csi'])],
            'catatan' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
