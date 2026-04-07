<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * KPI RESULTS
 *
 * Stores computed KPI results per assignment per period.
 * One row = one employee's result for one KPI in one period.
 *
 * Calculation flow:
 *   tasks / manual input
 *       ↓
 *   KpiCalculationJob
 *       ↓
 *   kpi_results (score, achievement_rate, final_score)
 *       ↓
 *   kpi_summaries (totals per user per period)
 *       ↓
 *   leaderboard (ranks)
 *
 * score_label mapping:
 *   achievement_rate > 100% → excellent
 *   80% – 100%              → good
 *   50% –  80%              → average
 *   < 50%                   → bad
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kpi_results', function (Blueprint $table) {
            $table->id();

            $table->foreignId('assignment_id')
                ->constrained('kpi_assignments')
                ->cascadeOnDelete();

            // Denormalized for fast reads without joining assignments
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('kpi_id')
                ->constrained('kpis')
                ->cascadeOnDelete();

            // Period this result belongs to
            // Format: "2026-04" (monthly), "2026-W15" (weekly), "2026-04-07" (daily)
            $table->string('period_label', 20)
                ->comment('ISO-style label: 2026-04 | 2026-W15 | 2026-04-07');

            $table->enum('period_type', ['daily', 'weekly', 'monthly', 'quarterly', 'yearly'])
                ->default('monthly');

            // The raw value achieved (could be Rp amount, count, percentage)
            $table->decimal('actual_value', 20, 4)->nullable();

            // Target that was active for this period (snapshot from assignment)
            $table->decimal('target_value', 20, 4)->nullable();

            // achievement_rate = actual_value / target_value * 100
            $table->decimal('achievement_rate', 8, 2)->nullable()
                ->comment('actual_value / target_value * 100');

            // raw score on 0-5 scale (legacy compatibility)
            $table->decimal('score', 5, 2)->nullable()
                ->comment('0–5 scale for legacy kpi_components scoring');

            // weighted contribution to total: score * (weight/100)
            $table->decimal('final_score', 8, 4)->nullable()
                ->comment('score * weight / 100');

            $table->enum('score_label', ['excellent', 'good', 'average', 'bad'])->nullable();

            // Supporting data
            $table->text('notes')->nullable();
            $table->string('evidence_path', 500)->nullable();

            // Workflow
            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected'])
                ->default('draft');

            $table->foreignId('submitted_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('calculated_at')->nullable();

            $table->timestamps();

            // Prevent duplicate result for same assignment + period
            $table->unique(['assignment_id', 'period_label'], 'uniq_result_assignment_period');

            // Most queried: "all results for user X in period Y"
            $table->index(['user_id', 'period_label', 'period_type'], 'idx_results_user_period');

            // Analytics: "all results for KPI Y in period Z"
            $table->index(['kpi_id', 'period_label'], 'idx_results_kpi_period');

            // Dashboard: filter by score_label
            $table->index(['score_label', 'period_label'], 'idx_results_label_period');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_results');
    }
};
