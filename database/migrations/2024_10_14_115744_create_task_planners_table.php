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
        Schema::create('task_planners', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->longtext('description')->nullable();
            $table->dateTime('next_run_at');
            $table->foreignId('campus_id');
            $table->foreignId('visit_id');
            $table->foreignId('space_id')->nullable();
            $table->foreignId('space_to_id')->nullable();
            $table->foreignId('task_type_id');
            $table->enum('on_holiday', ['Yes', 'No', 'OnlyOnHolidays']);
            $table->enum('frequency', ['Daily', 'Weekly', 'Monthly', 'Quarterly', 'EachXDay', 'SpecificDays', 'WeekdayInMonth']);
            $table->json('interval')->nullable();
            $table->json('assignments')->nullable();
            $table->json('excluded_dates')->nullable();
            $table->json('assets')->nullable();  // For files
            $table->enum('action', ['Add', 'Replace']);
            $table->string('comment')->nullable();
            $table->boolean('is_active'); // To mark task as active/inactive
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_planners');
    }
};
