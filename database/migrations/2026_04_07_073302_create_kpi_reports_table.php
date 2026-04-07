<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('kpi_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('kpi_component_id')->constrained('kpi_components')->cascadeOnDelete();
            $table->enum('period_type', ['daily', 'weekly', 'monthly'])->default('monthly');
            $table->date('tanggal');
            $table->string('period_label', 100);
            $table->decimal('nilai_target', 15, 4)->nullable();
            $table->decimal('nilai_aktual', 15, 4)->nullable();
            $table->decimal('persentase', 8, 2)->nullable()->comment('actual/target*100');
            $table->enum('score_label', ['excellent', 'good', 'average', 'bad'])->nullable();
            $table->text('catatan')->nullable();
            $table->string('file_evidence')->nullable();
            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected'])->default('draft');
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kpi_reports');
    }
};
