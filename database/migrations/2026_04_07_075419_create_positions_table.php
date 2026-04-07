<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * NORMALIZATION: positions (jabatan)
 * Replaces free-text 'jabatan' column on users, kpi_components, sla.
 *
 * Existing jabatan values migrated:
 *   users        : Direktur Utama, Direktur, HR & GA Manager, Marketing & Sales, Finance
 *   kpi_components: IT Staff, Sales Executive, Digital Marketing Specialist,
 *                   Research Analyst, Data Analyst, Marketing & Sales, Finance
 *   sla          : Finance, Accounting, Marketing & Sales
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('positions', function (Blueprint $table) {
            $table->id();

            $table->string('nama', 100);
            $table->string('kode', 40)->unique();

            $table->foreignId('department_id')
                ->nullable()
                ->constrained('departments')
                ->nullOnDelete();

            $table->enum('level', ['staff', 'supervisor', 'manager', 'director', 'executive'])
                ->default('staff');

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['department_id', 'is_active'], 'idx_pos_dept_active');
            $table->index('level', 'idx_pos_level');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('positions');
    }
};
