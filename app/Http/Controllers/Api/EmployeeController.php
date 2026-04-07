<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Resources\UserResource;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class EmployeeController extends ApiController
{
    public function index(Request $request)
    {
        $employees = User::query()
            ->when($request->filled('jabatan'), fn ($query) => $query->where('jabatan', $request->string('jabatan')))
            ->when($request->filled('departemen'), fn ($query) => $query->where('departemen', $request->string('departemen')))
            ->orderBy('nama')
            ->paginate((int) $request->input('per_page', 15));

        return $this->paginated(UserResource::collection($employees), $employees);
    }

    public function store(StoreEmployeeRequest $request)
    {
        $payload = $request->validated();
        $payload['password'] = $this->buildIdentitySecret($payload['nip'], $payload['nama']);

        $employee = User::create($payload);

        ActivityLog::record(
            $request->user(),
            'employee.created',
            User::class,
            $employee->id,
            ['nip' => $employee->nip, 'nama' => $employee->nama],
            $request
        );

        return $this->resource(new UserResource($employee), 'Pegawai berhasil ditambahkan.', Response::HTTP_CREATED);
    }

    public function update(StoreEmployeeRequest $request, User $employee)
    {
        $payload = $request->validated();

        if (
            $employee->nip !== $payload['nip'] ||
            $employee->nama !== $payload['nama']
        ) {
            $payload['password'] = $this->buildIdentitySecret($payload['nip'], $payload['nama']);
        }

        $employee->update($payload);

        ActivityLog::record(
            $request->user(),
            'employee.updated',
            User::class,
            $employee->id,
            ['nip' => $employee->nip, 'nama' => $employee->nama],
            $request
        );

        return $this->resource(new UserResource($employee->refresh()), 'Pegawai berhasil diperbarui.');
    }

    public function destroy(Request $request, User $employee)
    {
        $payload = ['nip' => $employee->nip, 'nama' => $employee->nama];
        $employee->tokens()->delete();
        $employee->delete();

        ActivityLog::record(
            $request->user(),
            'employee.deleted',
            User::class,
            $employee->id,
            $payload,
            $request
        );

        return $this->success(null, 'Pegawai berhasil dihapus.');
    }

    private function buildIdentitySecret(string $nip, string $nama): string
    {
        return Hash::make(strtolower(trim($nip).'|'.trim($nama)));
    }
}
