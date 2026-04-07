<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskMappingRequest;
use App\Http\Resources\TaskResource;
use App\Models\ActivityLog;
use App\Models\Task;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TaskController extends ApiController
{
    public function index(Request $request)
    {
        $user = $request->user();

        $tasks = Task::query()
            ->with(['user', 'kpiComponent', 'mapper'])
            ->when($user->isPegawai(), fn ($query) => $query->where('user_id', $user->id))
            ->when($request->filled('user_id') && !$user->isPegawai(), fn ($query) => $query->where('user_id', $request->integer('user_id')))
            ->when($request->filled('bulan'), fn ($query) => $query->whereMonth('tanggal', $request->integer('bulan')))
            ->when($request->filled('tahun'), fn ($query) => $query->whereYear('tanggal', $request->integer('tahun')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->latest('tanggal')
            ->paginate((int) $request->input('per_page', 15));

        return $this->paginated(TaskResource::collection($tasks), $tasks);
    }

    public function store(StoreTaskRequest $request)
    {
        $task = $request->user()->tasks()->create($request->validated());

        ActivityLog::record(
            $request->user(),
            'task.created',
            Task::class,
            $task->id,
            ['judul' => $task->judul],
            $request
        );

        return $this->resource(new TaskResource($task->load(['user', 'kpiComponent', 'mapper'])), 'Pekerjaan berhasil ditambahkan.', Response::HTTP_CREATED);
    }

    public function update(StoreTaskRequest $request, Task $task)
    {
        $this->authorize('delete', $task);

        $task->update($request->validated());

        ActivityLog::record(
            $request->user(),
            'task.updated',
            Task::class,
            $task->id,
            ['judul' => $task->judul],
            $request
        );

        return $this->resource(new TaskResource($task->load(['user', 'kpiComponent', 'mapper'])), 'Pekerjaan berhasil diperbarui.');
    }

    public function destroy(Request $request, Task $task)
    {
        $this->authorize('delete', $task);

        $payload = ['judul' => $task->judul, 'user_id' => $task->user_id];
        $task->delete();

        ActivityLog::record(
            $request->user(),
            'task.deleted',
            Task::class,
            $task->id,
            $payload,
            $request
        );

        return $this->success(null, 'Pekerjaan berhasil dihapus.');
    }

    public function mapping(UpdateTaskMappingRequest $request, Task $task)
    {
        if (!$request->user()->canManageAllData()) {
            return $this->error('Akses ditolak.', status: Response::HTTP_FORBIDDEN);
        }

        $task->update([
            'kpi_component_id' => $request->integer('kpi_component_id'),
            'manual_score' => $request->input('manual_score'),
            'mapped_by' => $request->user()->id,
            'mapped_at' => now(),
        ]);

        ActivityLog::record(
            $request->user(),
            'task.mapped',
            Task::class,
            $task->id,
            $request->validated(),
            $request
        );

        return $this->resource(new TaskResource($task->load(['user', 'kpiComponent', 'mapper'])), 'Mapping KPI berhasil diperbarui.');
    }
}
