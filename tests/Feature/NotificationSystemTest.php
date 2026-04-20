<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\KpiIndicator;
use App\Models\KpiNotification;
use App\Models\KpiScore;
use App\Models\Task;
use App\Models\TaskScore;
use App\Models\User;
use App\Services\KpiService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationSystemTest extends TestCase
{
    use RefreshDatabase;

    // ──────────────────────────────────────────────────────────────
    // 1. Task Assigned → kpi_notifications
    // ──────────────────────────────────────────────────────────────

    public function test_task_assigned_creates_kpi_notification(): void
    {
        $hr       = User::factory()->create(['role' => 'hr_manager']);
        $employee = User::factory()->create(['role' => 'pegawai']);

        Sanctum::actingAs($hr);

        $this->postJson('/api/tasks', [
            'title'       => 'Presentasi produk ke klien baru',
            'description' => 'Siapkan deck presentasi 15 slide.',
            'assigned_to' => $employee->id,
            'start_date'  => '2026-04-10',
            'end_date'    => '2026-04-12',
            'weight'      => 15,
            'status'      => 'pending',
        ])->assertCreated();

        $this->assertDatabaseHas('kpi_notifications', [
            'user_id' => $employee->id,
            'type'    => 'task_assigned',
        ]);

        $notif = KpiNotification::where('user_id', $employee->id)
            ->where('type', 'task_assigned')
            ->first();

        $this->assertNotNull($notif);
        $this->assertStringContainsString('Presentasi produk ke klien baru', $notif->body);
        $this->assertStringContainsString($hr->nama, $notif->body);
        $this->assertNull($notif->read_at, 'Notification should start as unread');
    }

    public function test_task_assigned_notification_not_duplicate_per_assign(): void
    {
        $hr       = User::factory()->create(['role' => 'hr_manager']);
        $employee = User::factory()->create(['role' => 'pegawai']);

        Sanctum::actingAs($hr);

        // Two different task assignments → two separate notifications (not deduplicated)
        $this->postJson('/api/tasks', [
            'title'       => 'Task A',
            'assigned_to' => $employee->id,
            'start_date'  => '2026-04-10',
            'end_date'    => '2026-04-11',
            'weight'      => 10,
            'status'      => 'pending',
        ])->assertCreated();

        $this->postJson('/api/tasks', [
            'title'       => 'Task B',
            'assigned_to' => $employee->id,
            'start_date'  => '2026-04-12',
            'end_date'    => '2026-04-13',
            'weight'      => 10,
            'status'      => 'pending',
        ])->assertCreated();

        $count = KpiNotification::where('user_id', $employee->id)
            ->where('type', 'task_assigned')
            ->count();

        $this->assertEquals(2, $count, 'Each task assignment should produce exactly one notification');
    }

    // ──────────────────────────────────────────────────────────────
    // 2. KPI Updated → kpi_notifications
    // ──────────────────────────────────────────────────────────────

    public function test_kpi_input_creates_kpi_updated_notification(): void
    {
        $hr   = User::factory()->create(['role' => 'hr_manager']);
        $dept = Department::factory()->create(['kode' => 'ACT', 'nama' => 'Accounting']);

        $employee = User::factory()->create(['role' => 'pegawai', 'department_id' => $dept->id]);

        $indicator = KpiIndicator::factory()->create([
            'name'          => 'Zero Error Dokumen',
            'weight'        => 40,
            'department_id' => $dept->id,
            'formula'       => ['type' => 'zero_penalty'],
        ]);

        Sanctum::actingAs($hr);

        $this->postJson('/api/kpi/input', [
            'user_id'      => $employee->id,
            'indicator_id' => $indicator->id,
            'target_value' => 0,
            'actual_value' => 0,
            'period_type'  => 'monthly',
            'period'       => '2026-04-01',
        ])->assertCreated();

        $this->assertDatabaseHas('kpi_notifications', [
            'user_id' => $employee->id,
            'type'    => 'kpi_updated',
        ]);

        $this->assertDatabaseHas('kpi_notifications', [
            'user_id' => $hr->id,
            'type'    => 'kpi_updated',
        ]);

        $notif = KpiNotification::where('user_id', $employee->id)
            ->where('type', 'kpi_updated')
            ->first();

        $this->assertStringContainsString('Zero Error Dokumen', $notif->body);
        $this->assertNotNull($notif->payload);
        $this->assertEquals($indicator->id, $notif->payload['indicator_id']);

        $hrNotif = KpiNotification::where('user_id', $hr->id)
            ->where('type', 'kpi_updated')
            ->first();

        $this->assertStringContainsString($employee->nama, $hrNotif->body);
        $this->assertEquals('hr', $hrNotif->payload['audience']);
        $this->assertEquals($employee->id, $hrNotif->payload['employee_id']);
    }

    // ──────────────────────────────────────────────────────────────
    // 3. Low Performance → kpi_notifications (with deduplication)
    // ──────────────────────────────────────────────────────────────

    public function test_low_performance_notification_created_when_score_below_threshold(): void
    {
        $hr   = User::factory()->create(['role' => 'hr_manager']);
        $dept = Department::factory()->create(['kode' => 'DRV', 'nama' => 'Driver Dept']);

        $employee = User::factory()->create(['role' => 'pegawai', 'department_id' => $dept->id]);

        $indicator = KpiIndicator::factory()->create([
            'name'          => 'On-time Delivery',
            'weight'        => 100,
            'department_id' => $dept->id,
            'formula'       => ['type' => 'percentage'],
        ]);

        Sanctum::actingAs($hr);

        // Score will be 30 (below threshold of 60)
        $this->postJson('/api/kpi/input', [
            'user_id'      => $employee->id,
            'indicator_id' => $indicator->id,
            'target_value' => 100,
            'actual_value' => 30,
            'period_type'  => 'monthly',
            'period'       => '2026-04-01',
        ])->assertCreated();

        $this->assertDatabaseHas('kpi_notifications', [
            'user_id' => $employee->id,
            'type'    => 'low_performance',
        ]);

        $this->assertDatabaseHas('kpi_notifications', [
            'user_id' => $hr->id,
            'type'    => 'low_performance',
        ]);

        $notif = KpiNotification::where('user_id', $employee->id)
            ->where('type', 'low_performance')
            ->first();

        $this->assertStringContainsString('30.00', $notif->body);

        $hrNotif = KpiNotification::where('user_id', $hr->id)
            ->where('type', 'low_performance')
            ->first();

        $this->assertStringContainsString($employee->nama, $hrNotif->body);
        $this->assertEquals('hr', $hrNotif->payload['audience']);
        $this->assertEquals($employee->id, $hrNotif->payload['employee_id']);
    }

    public function test_low_performance_notification_not_duplicated_in_same_period(): void
    {
        $hr   = User::factory()->create(['role' => 'hr_manager']);
        $dept = Department::factory()->create(['kode' => 'OBY', 'nama' => 'Office Boy Dept']);

        $employee = User::factory()->create(['role' => 'pegawai', 'department_id' => $dept->id]);

        $indicator = KpiIndicator::factory()->create([
            'name'          => 'Cleanliness Score',
            'weight'        => 100,
            'department_id' => $dept->id,
            'formula'       => ['type' => 'percentage'],
        ]);

        Sanctum::actingAs($hr);

        $payload = [
            'user_id'      => $employee->id,
            'indicator_id' => $indicator->id,
            'target_value' => 100,
            'actual_value' => 20,   // 20 → below threshold
            'period_type'  => 'monthly',
            'period'       => '2026-04-01',
        ];

        // Call inputRecord twice in the same month
        $this->postJson('/api/kpi/input', $payload)->assertCreated();
        $this->postJson('/api/kpi/input', array_merge($payload, ['actual_value' => 25]))->assertCreated();

        $count = KpiNotification::where('user_id', $employee->id)
            ->where('type', 'low_performance')
            ->whereMonth('created_at', 4)
            ->whereYear('created_at', 2026)
            ->count();

        $this->assertEquals(1, $count, 'Only one low-performance notification per period should be created');

        $hrCount = KpiNotification::where('user_id', $hr->id)
            ->where('type', 'low_performance')
            ->where('payload->audience', 'hr')
            ->where('payload->employee_id', $employee->id)
            ->count();

        $this->assertEquals(1, $hrCount, 'Only one HR low-performance alert per employee period should be created');
    }

    public function test_no_low_performance_notification_when_score_is_good(): void
    {
        $hr   = User::factory()->create(['role' => 'hr_manager']);
        $dept = Department::factory()->create(['kode' => 'RND2', 'nama' => 'R&D Dept']);

        $employee = User::factory()->create(['role' => 'pegawai', 'department_id' => $dept->id]);

        $indicator = KpiIndicator::factory()->create([
            'name'          => 'Research Output',
            'weight'        => 100,
            'department_id' => $dept->id,
            'formula'       => ['type' => 'percentage'],
        ]);

        Sanctum::actingAs($hr);

        // Score will be 90 (above threshold of 60)
        $this->postJson('/api/kpi/input', [
            'user_id'      => $employee->id,
            'indicator_id' => $indicator->id,
            'target_value' => 100,
            'actual_value' => 90,
            'period_type'  => 'monthly',
            'period'       => '2026-04-01',
        ])->assertCreated();

        $this->assertDatabaseMissing('kpi_notifications', [
            'user_id' => $employee->id,
            'type'    => 'low_performance',
        ]);
    }

    // ──────────────────────────────────────────────────────────────
    // 4. Notification API endpoint
    // ──────────────────────────────────────────────────────────────

    public function test_notification_api_returns_user_notifications_with_unread_count(): void
    {
        $employee = User::factory()->create(['role' => 'pegawai']);

        KpiNotification::create([
            'user_id' => $employee->id,
            'type'    => 'task_assigned',
            'title'   => 'Task A',
            'body'    => 'Task A diberikan.',
        ]);

        KpiNotification::create([
            'user_id' => $employee->id,
            'type'    => 'kpi_updated',
            'title'   => 'KPI Updated',
            'body'    => 'KPI Anda diperbarui.',
            'read_at' => now(),
        ]);

        Sanctum::actingAs($employee);

        $response = $this->getJson('/api/notifications');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data.items')
            ->assertJsonPath('data.unread_count', 1);
    }

    public function test_mark_notification_as_read(): void
    {
        $employee = User::factory()->create(['role' => 'pegawai']);

        $notif = KpiNotification::create([
            'user_id' => $employee->id,
            'type'    => 'task_assigned',
            'title'   => 'Task test',
            'body'    => 'Body test.',
        ]);

        $this->assertNull($notif->read_at);

        Sanctum::actingAs($employee);

        $this->putJson("/api/notifications/{$notif->id}/read")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('kpi_notifications', [
            'id'      => $notif->id,
            'user_id' => $employee->id,
        ]);

        $notif->refresh();
        $this->assertNotNull($notif->read_at);
    }

    public function test_mark_all_notifications_as_read(): void
    {
        $employee = User::factory()->create(['role' => 'pegawai']);

        KpiNotification::factory()->count(3)->create([
            'user_id' => $employee->id,
            'read_at' => null,
        ]);

        Sanctum::actingAs($employee);

        $this->putJson('/api/notifications/read-all')
            ->assertOk();

        $unread = KpiNotification::where('user_id', $employee->id)
            ->whereNull('read_at')
            ->count();

        $this->assertEquals(0, $unread, 'All notifications should be marked as read');
    }

    public function test_cannot_mark_other_users_notification_as_read(): void
    {
        $owner = User::factory()->create(['role' => 'pegawai']);
        $other = User::factory()->create(['role' => 'pegawai']);

        $notif = KpiNotification::create([
            'user_id' => $owner->id,
            'type'    => 'task_assigned',
            'title'   => 'Private task',
            'body'    => 'Body.',
        ]);

        Sanctum::actingAs($other);

        $this->putJson("/api/notifications/{$notif->id}/read")
            ->assertForbidden();

        $notif->refresh();
        $this->assertNull($notif->read_at, 'Notification should remain unread');
    }
}
