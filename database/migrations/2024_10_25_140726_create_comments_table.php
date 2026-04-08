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
            $table->foreignId('created_by');
            $table->enum('event', [ 'task_created','task_started','task_updated','task_rejected','task_completed','task_help_requested','task_help_given','announcement' ]);
            $table->foreignId('task_id')->nullable()->constrained('tasks')->onDelete('cascade');
            $table->foreignId('status_id')->nullable();
            $table->boolean('help_requested')->nullable();
            $table->longText('content')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->json('metadata')->nullable();
            $table->json('recipient_users')->nullable();
            $table->json('recipient_teams')->nullable();
            $table->json('read_by')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        // Drop the pivot table first to avoid foreign key issues
        Schema::dropIfExists('comment_task');
        Schema::dropIfExists('comments');
    }
};
