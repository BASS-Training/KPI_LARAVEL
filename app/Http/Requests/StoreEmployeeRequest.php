<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class StoreEmployeeRequest extends SanitizedFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $employeeId = $this->route('employee')?->id;

        return [
            'nip' => ['required', 'string', 'max:50', Rule::unique('users', 'nip')->ignore($employeeId)],
            'nama' => ['required', 'string', 'max:255'],
            'jabatan' => ['required', 'string', 'max:255'],
            'departemen' => ['required', 'string', 'max:255'],
            'status_karyawan' => ['required', 'string', 'max:100'],
            'tanggal_masuk' => ['required', 'date'],
            'no_hp' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($employeeId)],
            'role' => ['required', Rule::in(['pegawai', 'hr_manager', 'direktur'])],
            'division_id' => ['nullable', 'exists:divisions,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'position_id' => ['nullable', 'exists:positions,id'],
        ];
    }
}
