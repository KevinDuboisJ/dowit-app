<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Create comments table
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('task_id')->nullable();
            $table->foreignId('status_id')->nullable();
            $table->boolean('needs_help')->nullable();
            $table->text('content')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->json('metadata')->nullable();
            $table->json('recipient_users')->nullable();
            $table->json('recipient_teams')->nullable();
            $table->json('read_by')->nullable();
            $table->timestamps();
        });

        // Create the pivot table for tasks and comments
        // Schema::create('comment_task', function (Blueprint $table) {
        //     $table->id();
        //     $table->foreignId('task_id')->constrained()->onDelete('cascade');
        //     $table->foreignId('comment_id')->constrained()->onDelete('cascade');
        // });
    }

    public function down()
    {
        // Drop the pivot table first to avoid foreign key issues
        Schema::dropIfExists('comment_task');
        Schema::dropIfExists('comments');
    }
};
