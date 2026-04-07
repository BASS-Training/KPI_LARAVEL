<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class StoreKpiReportRequest extends SanitizedFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['nullable', 'exists:users,id'],
            'kpi_component_id' => ['required', 'exists:kpi_components,id'],
            'period_type' => ['required', Rule::in(['daily', 'weekly', 'monthly'])],
            'tanggal' => ['required', 'date'],
            'period_label' => ['required', 'string', 'max:100'],
            'nilai_target' => ['nullable', 'numeric', 'min:0'],
            'nilai_aktual' => ['required', 'numeric', 'min:0'],
            'catatan' => ['nullable', 'string'],
            'status' => ['nullable', Rule::in(['draft', 'submitted', 'approved', 'rejected'])],
        ];
    }
}
