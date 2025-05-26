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
        Schema::create('task_planner_team', function (Blueprint $table) {
            $table->id();

            // Foreign key to task_planners.id
            $table->foreignId('task_planner_id')
                ->constrained('task_planners')
                ->cascadeOnDelete();

            // Foreign key to teams.id
            $table->foreignId('team_id')
                ->constrained()
                ->cascadeOnDelete();

            // Prevent the same (task_planner, team) pair twice
            $table->unique(['task_planner_id', 'team_id']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_planner_team');
    }
};
