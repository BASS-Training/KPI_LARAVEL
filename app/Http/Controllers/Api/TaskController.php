<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskStatusRequest;
use App\Http\Requests\UpdateTaskMappingRequest;
use App\Http\Resources\TaskResource;
use App\Models\ActivityLog;
use App\Models\Task;
use App\Services\TaskAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class TaskController extends ApiController
{
    public function __construct(private readonly TaskAssignmentService $taskAssignmentService)
    {
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $tasks = Task::query()
            ->with(['user', 'assignee', 'assigner', 'kpiComponent', 'mapper', 'taskScores'])
            ->when($user->isPegawai(), fn ($query) => $query->where(fn ($inner) => $inner
                ->where('user_id', $user->id)
                ->orWhere('assigned_to', $user->id)))
            ->when($request->filled('user_id') && !$user->isPegawai(), fn ($query) => $query->where('user_id', $request->integer('user_id')))
            ->when($request->filled('assigned_to') && !$user->isPegawai(), fn ($query) => $query->where('assigned_to', $request->integer('assigned_to')))
            ->when($request->filled('task_type'), fn ($query) => $query->where('task_type', $request->string('task_type')))
            ->when($request->filled('bulan'), fn ($query) => $query->whereMonth(DB::raw('COALESCE(end_date, tanggal)'), $request->integer('bulan')))
            ->when($request->filled('tahun'), fn ($query) => $query->whereYear(DB::raw('COALESCE(end_date, tanggal)'), $request->integer('tahun')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', Task::statusForStorage((string) $request->input('status'))))
            ->latest(DB::raw('COALESCE(end_date, tanggal)'))
            ->paginate((int) $request->input('per_page', 15));

        return $this->paginated(TaskResource::collection($tasks), $tasks);
    }

    public function store(StoreTaskRequest $request)
    {
        if ($request->isManualAssignmentPayload()) {
            if (!$request->user()->canManageAllData()) {
                return $this->error('Akses ditolak.', status: Response::HTTP_FORBIDDEN);
            }

            $task = $this->taskAssignmentService->create($request->validated(), $request->user());

            ActivityLog::record(
                $request->user(),
                'task.assigned',
                Task::class,
                $task->id,
                ['judul' => $task->judul, 'assigned_to' => $task->assigned_to_user_id],
                $request
            );

            return $this->resource(new TaskResource($task), 'Task KPI berhasil di-assign.', Response::HTTP_CREATED);
        }

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
        if ($task->isManualAssignment() || $request->isManualAssignmentPayload()) {
            if (!$request->user()->canManageAllData()) {
                return $this->error('Akses ditolak.', status: Response::HTTP_FORBIDDEN);
            }

            $task = $this->taskAssignmentService->update($task, $request->validated(), $request->user());

            ActivityLog::record(
                $request->user(),
                'task.assignment_updated',
                Task::class,
                $task->id,
                ['judul' => $task->judul, 'assigned_to' => $task->assigned_to_user_id],
                $request
            );

            return $this->resource(new TaskResource($task), 'Task KPI berhasil diperbarui.');
        }

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
        if ($task->isManualAssignment()) {
            if (!$request->user()->canManageAllData()) {
                return $this->error('Akses ditolak.', status: Response::HTTP_FORBIDDEN);
            }

            $payload = ['judul' => $task->judul, 'assigned_to' => $task->assigned_to_user_id];
            $this->taskAssignmentService->delete($task);

            ActivityLog::record(
                $request->user(),
                'task.assignment_deleted',
                Task::class,
                $task->id,
                $payload,
                $request
            );

            return $this->success(null, 'Task KPI berhasil dihapus.');
        }

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

    public function myTasks(Request $request)
    {
        $tasks = Task::query()
            ->with(['assignee', 'assigner', 'taskScores'])
            ->where('task_type', Task::TYPE_MANUAL_ASSIGNMENT)
            ->where('assigned_to', $request->user()->id)
            ->latest('end_date')
            ->paginate((int) $request->input('per_page', 15));

        return $this->paginated(TaskResource::collection($tasks), $tasks);
    }

    public function updateStatus(UpdateTaskStatusRequest $request, Task $task)
    {
        if (!$task->isManualAssignment()) {
            return $this->error('Endpoint ini hanya untuk task assignment HR.', status: Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ((int) $task->assigned_to_user_id !== (int) $request->user()->id && !$request->user()->canManageAllData()) {
            return $this->error('Akses ditolak.', status: Response::HTTP_FORBIDDEN);
        }

        $task = $this->taskAssignmentService->updateStatus($task, $request->validated());

        ActivityLog::record(
            $request->user(),
            'task.status_updated',
            Task::class,
            $task->id,
            ['status' => $task->status_code, 'actual_value' => $task->actual_value],
            $request
        );

        return $this->resource(new TaskResource($task), 'Status task berhasil diperbarui.');
    }
}
