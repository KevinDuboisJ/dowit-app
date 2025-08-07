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
        Schema::create('tag_team', function (Blueprint $table) {
            // Surrogate PK
            $table->id();

            // FKs
            $table->foreignId('tag_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('team_id')
                ->constrained()
                ->cascadeOnDelete();

            // Uniqueness constraint to prevent duplicates
            $table->unique(['tag_id', 'team_id']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tag_team');
    }
};
