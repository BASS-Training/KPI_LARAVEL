<?php

namespace Tests\Feature;

use App\Models\KpiIndicator;
use App\Models\Role;
use App\Models\User;
use App\Services\KpiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class KpiAutomationTest extends TestCase
{
    use RefreshDatabase;

    public function test_generate_monthly_kpi_creates_records_without_duplicates(): void
    {
        $role = Role::query()->create([
            'name' => 'Support',
            'slug' => 'support',
        ]);

        $user = User::factory()->create([
            'role' => 'pegawai',
            'role_id' => $role->id,
        ]);

        KpiIndicator::query()->create([
            'name' => 'Ticket Resolution',
            'description' => 'Jumlah tiket selesai',
            'weight' => 50,
            'default_target_value' => 80,
            'role_id' => $role->id,
        ]);

        /** @var KpiService $service */
        $service = app(KpiService::class);

        $service->generateMonthlyKPI('2026-04-01');
        $service->generateMonthlyKPI('2026-04-01');

        $this->assertDatabaseCount('kpi_records', 1);
        $this->assertDatabaseCount('kpi_targets', 1);
        $this->assertDatabaseCount('kpi_scores', 1);
        $this->assertDatabaseHas('kpi_records', [
            'user_id' => $user->id,
            'target_value' => 80,
        ]);
    }

    public function test_low_score_input_creates_database_notification(): void
    {
        $actor = User::factory()->create(['role' => 'hr_manager']);
        $role = Role::query()->create([
            'name' => 'Warehouse',
            'slug' => 'warehouse',
        ]);

        $employee = User::factory()->create([
            'role' => 'pegawai',
            'role_id' => $role->id,
        ]);

        $indicator = KpiIndicator::query()->create([
            'name' => 'Inventory Accuracy',
            'description' => 'Akurasi stok',
            'weight' => 100,
            'default_target_value' => 100,
            'role_id' => $role->id,
        ]);

        Sanctum::actingAs($actor);

        $this->postJson('/api/kpi/input', [
            'user_id' => $employee->id,
            'indicator_id' => $indicator->id,
            'target_value' => 100,
            'actual_value' => 20,
            'period_type' => 'monthly',
            'period' => '2026-04-13',
        ])->assertCreated();

        $this->assertDatabaseCount('notifications', 1);
        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $employee->id,
        ]);
    }
}
