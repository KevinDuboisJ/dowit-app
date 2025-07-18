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
            $table->dateTime('start_date_time'); // Task start date and time
            $table->foreignId('campus_id');
            $table->foreignId('patient_id');
            $table->foreignId('space_id')->nullable();
            $table->foreignId('space_to_id')->nullable();
            $table->foreignId('task_type_id');
            $table->boolean('is_active'); // To mark task as active/inactive
            $table->enum('on_holiday', ['Yes', 'No', 'OnlyOnHolidays']);
            $table->enum('frequency', ['Daily', 'Weekly', 'Monthly', 'Quarterly', 'EachXDay', 'SpecificDays', 'WeekdayInMonth']);
            $table->json('interval')->nullable();  // For interval frequency
            $table->json('assignments')->nullable();  // For user assignation
            $table->json('assets')->nullable();  // For files
            $table->enum('action', ['Add', 'Replace']);
            $table->string('comment')->nullable(); 
            $table->dateTime('next_run_at');
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
