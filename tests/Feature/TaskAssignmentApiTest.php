<?php

namespace Tests\Feature;

use App\Models\KpiIndicator;
use App\Models\Role;
use App\Models\Task;
use App\Models\TaskScore;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TaskAssignmentApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_hr_can_assign_manual_task_and_task_score_is_created(): void
    {
        $hr = User::factory()->create(['role' => 'hr_manager']);
        $role = Role::query()->create(['name' => 'Operations', 'slug' => 'operations']);
        $employee = User::factory()->create(['role' => 'pegawai', 'role_id' => $role->id]);

        KpiIndicator::query()->create([
            'name' => 'Delivery',
            'description' => 'Delivery KPI',
            'weight' => 40,
            'role_id' => $role->id,
        ]);

        Sanctum::actingAs($hr);

        $response = $this->postJson('/api/tasks', [
            'title' => 'Support event training batch April',
            'description' => 'Task manual dari HR di luar indikator KPI utama.',
            'assigned_to' => $employee->id,
            'start_date' => '2026-04-10',
            'end_date' => '2026-04-12',
            'weight' => 20,
            'status' => 'done',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.task_type', Task::TYPE_MANUAL_ASSIGNMENT)
            ->assertJsonPath('data.assigned_to', $employee->id)
            ->assertJsonPath('data.status_code', Task::STATUS_DONE);

        $taskId = $response->json('data.id');

        $this->assertDatabaseHas('task_scores', [
            'task_id' => $taskId,
            'user_id' => $employee->id,
            'period' => '2026-04',
            'score' => 20,
        ]);

        $dashboard = $this->getJson('/api/kpi/user/'.$employee->id.'?period_type=monthly&period=2026-04-12');

        $dashboard
            ->assertOk()
            ->assertJsonPath('data.raw_score', 20)
            ->assertJsonPath('data.breakdown.1.type', 'task')
            ->assertJsonPath('data.breakdown.1.task_id', $taskId)
            ->assertJsonPath('data.breakdown.1.score', 20);
    }

    public function test_assignee_can_update_status_and_score_recalculates(): void
    {
        $hr = User::factory()->create(['role' => 'hr_manager']);
        $employee = User::factory()->create(['role' => 'pegawai']);

        $task = Task::query()->create([
            'task_type' => Task::TYPE_MANUAL_ASSIGNMENT,
            'user_id' => $employee->id,
            'assigned_by' => $hr->id,
            'assigned_to' => $employee->id,
            'tanggal' => '2026-04-10',
            'start_date' => '2026-04-10',
            'end_date' => '2026-04-15',
            'judul' => 'Follow up lead lama',
            'jenis_pekerjaan' => 'Task KPI',
            'status' => Task::statusForStorage('pending'),
            'weight' => 30,
        ]);

        TaskScore::query()->create([
            'task_id' => $task->id,
            'user_id' => $employee->id,
            'score' => 0,
            'period' => '2026-04',
        ]);

        Sanctum::actingAs($employee);

        $response = $this->putJson('/api/tasks/'.$task->id.'/update-status', [
            'status' => 'on_progress',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.status_code', Task::STATUS_ON_PROGRESS);

        $this->assertDatabaseHas('task_scores', [
            'task_id' => $task->id,
            'score' => 15,
            'period' => '2026-04',
        ]);
    }

    public function test_my_tasks_returns_only_manual_assigned_tasks_for_user(): void
    {
        $hr = User::factory()->create(['role' => 'hr_manager']);
        $employee = User::factory()->create(['role' => 'pegawai']);
        $other = User::factory()->create(['role' => 'pegawai']);

        Task::query()->create([
            'task_type' => Task::TYPE_MANUAL_ASSIGNMENT,
            'user_id' => $employee->id,
            'assigned_by' => $hr->id,
            'assigned_to' => $employee->id,
            'tanggal' => '2026-04-10',
            'start_date' => '2026-04-10',
            'end_date' => '2026-04-10',
            'judul' => 'Task saya',
            'jenis_pekerjaan' => 'Task KPI',
            'status' => Task::statusForStorage('pending'),
            'weight' => 10,
        ]);

        Task::query()->create([
            'task_type' => Task::TYPE_MANUAL_ASSIGNMENT,
            'user_id' => $other->id,
            'assigned_by' => $hr->id,
            'assigned_to' => $other->id,
            'tanggal' => '2026-04-10',
            'start_date' => '2026-04-10',
            'end_date' => '2026-04-10',
            'judul' => 'Task orang lain',
            'jenis_pekerjaan' => 'Task KPI',
            'status' => Task::statusForStorage('pending'),
            'weight' => 10,
        ]);

        Sanctum::actingAs($employee);

        $response = $this->getJson('/api/my-tasks');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.assigned_to', $employee->id)
            ->assertJsonPath('data.items.0.title', 'Task saya');
    }
}
