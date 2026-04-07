<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class StoreTaskRequest extends SanitizedFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
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
}
