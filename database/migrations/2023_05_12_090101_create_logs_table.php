<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class createLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('logs', function (Blueprint $table) {
            $table->id();
            // Which record from the table are we referencing
            $table->bigInteger('traceable_id')->unsigned();
            // Which table are we tracking (model path).
            $table->string('traceable_type');
            // Who made the action.
            $table->foreignId('user_id')->constrained('users');
            // Source.
            $table->string('source');
            // What event happened.
            $table->string('event');
            // Level (e.g., DEBUG, INFO, WARN, ERROR, CRITICAL).
            $table->string('level')->default('DEBUG'); 
            // What fields were affected.
            $table->json('data')->nullable();
            // optional comment.
            $table->string('comment')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('logs');
    }
};
