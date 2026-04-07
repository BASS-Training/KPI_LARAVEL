<?php

namespace App\Http\Requests;

class UpdateTaskMappingRequest extends SanitizedFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'kpi_component_id' => ['required', 'exists:kpi_components,id'],
            'manual_score' => ['nullable', 'numeric', 'min:0', 'max:5'],
        ];
    }
}
