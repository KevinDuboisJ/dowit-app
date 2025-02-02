<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Disable foreign key checks before running the migration
        Schema::disableForeignKeyConstraints();

        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_planner_id')->nullable();
            $table->datetime('start_date_time');
            $table->string('name');
            $table->string('description', 500)->nullable();
            $table->foreignId('campus_id');
            $table->foreignId('task_type_id');
            $table->foreignId('patient_id')->nullable();
            $table->foreignId('space_id');
            $table->foreignId('space_to_id')->nullable();
            $table->foreignId('status_id');
            $table->enum('priority', ['low', 'medium', 'high'])->nullable();
            $table->boolean('needs_help')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Re-enable foreign key checks after running the migration
        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Disable foreign key checks before dropping the table
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('task_team');
        Artisan::call('migrate:refresh', ['--path' => 'database/migrations/2024_10_22_141943_create_task_team_table.php',]);

        Schema::dropIfExists('task_user');
        Artisan::call('migrate:refresh', ['--path' => 'database/migrations/2024_10_14_125627_create_task_user_table.php',]);

        Schema::dropIfExists('tasks');

        // Re-enable foreign key checks after dropping the table
        Schema::enableForeignKeyConstraints();
    }
};
