<?php

namespace Tests\Feature;

use App\Enums\TaskStatusEnum;
use App\Models\Task;
use App\Services\TaskService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TaskClaimConflictTest extends TestCase
{
    public function test_stale_start_cannot_replace_existing_assignee_even_in_the_same_second(): void
    {
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('task_type_id')->nullable();
            $table->unsignedInteger('status_id');
            $table->timestamps();
        });
        Schema::create('task_types', function (Blueprint $table) { $table->id(); });
        Schema::create('users', function (Blueprint $table) { $table->id(); });
        Schema::create('task_user', function (Blueprint $table) {
            $table->unsignedBigInteger('task_id');
            $table->unsignedBigInteger('user_id');
        });

        $guard = \Mockery::mock();
        $guard->shouldReceive('hasUser')->andReturn(false);
        Auth::shouldReceive('guard')->andReturn($guard);
        Auth::shouldReceive('id')->andReturn(2);
        Gate::shouldReceive('authorize')->with('execute', \Mockery::type(Task::class))->once();

        $time = Carbon::parse('2026-11-17 09:00:00');
        DB::table('tasks')->insert(['id' => 1, 'status_id' => TaskStatusEnum::Added->value, 'created_at' => $time, 'updated_at' => $time]);
        $staleTask = Task::findOrFail(1);

        // Another worker claims the task in the same second as the displayed version.
        DB::table('tasks')->where('id', 1)->update(['status_id' => TaskStatusEnum::InProgress->value]);
        DB::table('users')->insert(['id' => 1]);
        DB::table('task_user')->insert(['task_id' => 1, 'user_id' => 1]);

        $result = app(TaskService::class)->updateTask($staleTask, [
            'status_id' => TaskStatusEnum::InProgress->value,
            'assignees' => [2],
            'updated_at' => $time,
        ]);

        $this->assertTrue($result['conflict']);
        $this->assertSame([1], DB::table('task_user')->where('task_id', 1)->pluck('user_id')->all());
    }

    public function test_a_stale_edit_cannot_remove_a_recent_assignee_in_the_same_second(): void
    {
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('task_type_id')->nullable();
            $table->unsignedInteger('status_id');
            $table->string('priority')->nullable();
            $table->boolean('help_requested')->default(false);
            $table->timestamps();
        });
        Schema::create('task_types', function (Blueprint $table) { $table->id(); });
        Schema::create('users', function (Blueprint $table) { $table->id(); });
        Schema::create('tags', function (Blueprint $table) { $table->id(); });
        Schema::create('task_user', function (Blueprint $table) {
            $table->unsignedBigInteger('task_id');
            $table->unsignedBigInteger('user_id');
        });
        Schema::create('tag_task', function (Blueprint $table) {
            $table->unsignedBigInteger('task_id');
            $table->unsignedBigInteger('tag_id');
        });

        $guard = \Mockery::mock();
        $guard->shouldReceive('hasUser')->andReturn(false);
        Auth::shouldReceive('guard')->andReturn($guard);
        Auth::shouldReceive('id')->andReturn(3);
        Gate::shouldReceive('authorize')->with('update', \Mockery::type(Task::class))->once();
        Gate::shouldReceive('authorize')->with('execute', \Mockery::type(Task::class))->once();

        $time = Carbon::parse('2026-11-17 09:00:00');
        DB::table('tasks')->insert(['id' => 1, 'status_id' => TaskStatusEnum::InProgress->value, 'created_at' => $time, 'updated_at' => $time]);
        DB::table('users')->insert([['id' => 1], ['id' => 2], ['id' => 3]]);
        DB::table('task_user')->insert(['task_id' => 1, 'user_id' => 1]);
        $staleTask = Task::findOrFail(1);
        $staleTask->load('assignees', 'tags');
        $token = $staleTask->concurrency_token;
        DB::table('task_user')->insert(['task_id' => 1, 'user_id' => 2]);
        $this->assertSame($token, $staleTask->concurrency_token);
        $this->assertNotSame($token, $staleTask->calculateConcurrencyToken());

        $result = app(TaskService::class)->updateTask($staleTask, [
            'status_id' => TaskStatusEnum::InProgress->value,
            'assignees' => [1], 'tags' => [], 'comment' => 'Test',
            'updated_at' => $time, 'concurrency_token' => $token,
        ]);

        $this->assertTrue($result['conflict']);
        $this->assertEquals([1, 2], DB::table('task_user')->where('task_id', 1)->orderBy('user_id')->pluck('user_id')->all());

        // A second helper used an older list after the first help request was fulfilled.
        $helpResult = app(TaskService::class)->updateTask($staleTask, [
            'status_id' => TaskStatusEnum::InProgress->value,
            'assignees' => [1, 3], 'help_requested' => false,
            'updated_at' => $time,
        ]);
        $this->assertTrue($helpResult['conflict']);
        $this->assertEquals([1, 2], DB::table('task_user')->where('task_id', 1)->orderBy('user_id')->pluck('user_id')->all());
    }
}
