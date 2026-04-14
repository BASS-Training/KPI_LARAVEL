<?php

namespace App\Http\Requests;

use App\Models\Task;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends SanitizedFormRequest
{
    public function prepareForValidation(): void
    {
        if ($this->has('title') && !$this->has('judul')) {
            $this->merge(['judul' => $this->input('title')]);
        }

        if ($this->has('description') && !$this->has('deskripsi')) {
            $this->merge(['deskripsi' => $this->input('description')]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        if ($this->isManualAssignmentPayload()) {
            return [
                'title' => ['sometimes', 'required', 'string', 'max:255'],
                'judul' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                'deskripsi' => ['nullable', 'string'],
                'assigned_to' => ['required', 'exists:users,id'],
                'start_date' => ['required', 'date'],
                'end_date' => ['required', 'date', 'after_or_equal:start_date'],
                'weight' => ['required', 'numeric', 'min:0', 'max:100'],
                'target_value' => ['nullable', 'numeric', 'min:0'],
                'actual_value' => ['nullable', 'numeric', 'min:0'],
                'status' => ['required', Rule::in(['pending', 'on_progress', 'done', 'Pending', 'Dalam Proses', 'Selesai'])],
            ];
        }

        return [
            'tanggal' => ['required', 'date'],
            'judul' => ['required', 'string', 'max:255'],
            'jenis_pekerjaan' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in(['Selesai', 'Dalam Proses', 'Pending'])],
            'waktu_mulai' => ['nullable', 'date_format:H:i'],
            'waktu_selesai' => ['nullable', 'date_format:H:i'],
            'ada_delay' => ['nullable', 'boolean'],
            'ada_error' => ['nullable', 'boolean'],
            'ada_komplain' => ['nullable', 'boolean'],
            'deskripsi' => ['nullable', 'string'],
        ];
    }

    public function isManualAssignmentPayload(): bool
    {
        return $this->route('task')?->task_type === Task::TYPE_MANUAL_ASSIGNMENT
            || $this->hasAny(['assigned_to', 'start_date', 'end_date', 'weight', 'target_value', 'actual_value', 'title']);
    }
}
