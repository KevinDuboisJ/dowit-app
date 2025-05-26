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
        Schema::create('document_team', function (Blueprint $table) {
            // Surrogate PK
            $table->id();

            // FKs
            $table->foreignId('document_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('team_id')
                ->constrained()
                ->cascadeOnDelete();

            // Uniqueness constraint to prevent duplicates
            $table->unique(['document_id', 'team_id']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_team');
    }
};
