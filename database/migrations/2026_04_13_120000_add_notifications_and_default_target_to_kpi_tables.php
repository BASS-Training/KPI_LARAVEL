<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kpi_indicators', function (Blueprint $table) {
            $table->decimal('default_target_value', 12, 2)->default(config('kpi.default_target_value', 100))->after('weight');
        });

        Schema::table('kpi_scores', function (Blueprint $table) {
            $table->string('status', 20)->default('bad')->after('normalized_score');
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['notifiable_type', 'notifiable_id', 'read_at'], 'notifications_notifiable_read_index');
        });

        DB::table('roles')->updateOrInsert(
            ['slug' => 'admin'],
            [
                'name' => 'Admin',
                'description' => 'Full access administrator',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        DB::table('kpi_scores')
            ->select(['id', 'normalized_score'])
            ->orderBy('id')
            ->chunkById(100, function ($scores) {
                foreach ($scores as $score) {
                    DB::table('kpi_scores')
                        ->where('id', $score->id)
                        ->update([
                            'status' => match (true) {
                                (float) $score->normalized_score >= 80 => 'good',
                                (float) $score->normalized_score >= 60 => 'average',
                                default => 'bad',
                            },
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');

        Schema::table('kpi_scores', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('kpi_indicators', function (Blueprint $table) {
            $table->dropColumn('default_target_value');
        });
    }
};
