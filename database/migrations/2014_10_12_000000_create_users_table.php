<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->smallInteger('edb_id')->nullable()->unique();
            $table->string('firstname');
            $table->string('lastname');
            $table->string('username');
            $table->smallInteger('department_id')->unsigned()->nullable();
            $table->smallInteger('profession_id')->unsigned()->nullable();
            $table->string('password')->nullable();
            $table->dateTime('last_login')->nullable();
            $table->string('object_sid')->unique()->nullable();
            $table->string('image_path')->nullable();
            $table->string('email')->nullable();
            $table->boolean('is_active')->nullable();
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
        Schema::dropIfExists('users');
    }
}
