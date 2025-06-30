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
         Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code');
            $table->json('value');
            $table->enum('scope', ['global', 'team']);
            $table->foreignId('team_id');
            $table->timestamps();
        });

        Schema::create('setting_team', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id');
            $table->foreignId('setting_id');
            $table->json('value');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('setting_team');
    }
};
