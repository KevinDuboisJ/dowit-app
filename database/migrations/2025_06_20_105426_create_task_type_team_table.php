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
        Schema::create('task_type_team', function (Blueprint $table) {
            // Surrogate PK
            $table->id();

            // FKs
            $table->foreignId('task_type_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('team_id')
                ->constrained()
                ->cascadeOnDelete();

            // Uniqueness constraint to prevent duplicates
            $table->unique(['task_type_id', 'team_id']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_type_team');
    }
};
