<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateTaskStatusRequest extends SanitizedFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['pending', 'on_progress', 'done', 'Pending', 'Dalam Proses', 'Selesai'])],
            'actual_value' => ['nullable', 'numeric', 'min:0'],
            'end_date' => ['nullable', 'date'],
        ];
    }
}
