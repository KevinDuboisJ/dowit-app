<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('task_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // Status name
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('task_statuses');
    }
};
