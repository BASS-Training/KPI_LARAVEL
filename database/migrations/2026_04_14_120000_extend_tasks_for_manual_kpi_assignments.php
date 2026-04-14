<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->string('task_type')->default('legacy')->after('id');
            $table->foreignId('assigned_by')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->after('assigned_by')->constrained('users')->nullOnDelete();
            $table->date('start_date')->nullable()->after('tanggal');
            $table->date('end_date')->nullable()->after('start_date');
            $table->decimal('weight', 5, 2)->nullable()->after('mapped_at');
            $table->decimal('target_value', 12, 2)->nullable()->after('weight');
            $table->decimal('actual_value', 12, 2)->nullable()->after('target_value');
        });

        DB::table('tasks')
            ->whereNull('assigned_to')
            ->update([
                'assigned_to' => DB::raw('user_id'),
                'start_date' => DB::raw('tanggal'),
                'end_date' => DB::raw('tanggal'),
                'task_type' => 'legacy',
            ]);
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_by');
            $table->dropConstrainedForeignId('assigned_to');
            $table->dropColumn([
                'task_type',
                'start_date',
                'end_date',
                'weight',
                'target_value',
                'actual_value',
            ]);
        });
    }
};
