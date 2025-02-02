<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCampusesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('campuses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('acronym')->nullable();
            $table->string('address')->nullable();
            $table->timestamps();
        });
        // Schema::table('campus', function (Blueprint $table) {
        //     $table->string('address');
        // });
        // $table->integer('paid')->after('whichever_column');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('campuses');
        // Schema::table('campus', function($table) {
        //     $table->dropColumn('address');
        // });
    }
}
