<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class KpiDashboardRequest extends SanitizedFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'period_type' => ['nullable', Rule::in(['weekly', 'monthly'])],
            'period' => ['nullable', 'date'],
            'role_id' => ['nullable', 'integer', 'exists:roles,id'],
            'employee_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }

    public function filters(): array
    {
        return [
            'period_type' => $this->input('period_type', 'monthly'),
            'period' => $this->input('period', now()->toDateString()),
            'role_id' => $this->integer('role_id') ?: null,
            'employee_id' => $this->integer('employee_id') ?: null,
        ];
    }
}
