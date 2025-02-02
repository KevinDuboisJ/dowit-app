<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('task_assignment_rules', function (Blueprint $table) {
            $table->id();
            $table->string('description')->nullable();
            $table->json('campuses')->nullable();
            $table->json('task_types')->nullable();
            $table->json('spaces')->nullable();
            $table->json('spaces_to')->nullable();
            $table->timestamps();
        });

        // Create the pivot table for linking rules to multiple teams
        Schema::create('task_assignment_rule_team', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->onDelete('cascade');
            $table->foreignId('task_assignment_rule_id')->constrained('task_assignment_rules')->onDelete('cascade');
        });
        
        // // Create the pivot table for linking rules to multiple teams
        // Schema::create('task_assignment_rule_task_type', function (Blueprint $table) {
        //     $table->id();
        //     $table->foreignId('task_types')->constrained('task_types')->onDelete('cascade');
        //     $table->foreignId('task_assignment_rule_id')->constrained('task_assignment_rules')->onDelete('cascade');
        // });

        // // Create the pivot table for linking rules to multiple teams
        // Schema::create('task_assignment_rule_space', function (Blueprint $table) {
        //     $table->id();
        //     $table->foreignId('space_id')->constrained()->onDelete('cascade');
        //     $table->foreignId('task_assignment_rule_id')->constrained('task_assignment_rules')->onDelete('cascade');
        // });

        // // Create the pivot table for linking rules to multiple teams
        // Schema::create('task_assignment_rule_space_to', function (Blueprint $table) {
        //     $table->id();
        //     $table->foreignId('space_id')->constrained()->onDelete('cascade');
        //     $table->foreignId('task_assignment_rule_id')->constrained('task_assignment_rules')->onDelete('cascade');
        // });
    }

    public function down()
    {
        Schema::dropIfExists('task_assignment_rule_team');
        Schema::dropIfExists('task_assignment_rules');
    }
};
