<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Events\BroadcastEvent;
use App\Http\Controllers\Api\V1\TaskController;
use App\Http\Requests\Api\V1\StoreTaskRequest;
use App\Services\TaskAssignmentService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TaskVisibilitySyncTest extends TestCase
{
    private function prepareVisibilityTables(): void
    {
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        $pdo = DB::connection()->getPdo();
        $pdo->sqliteCreateFunction('JSON_LENGTH', fn ($value) => count(json_decode($value, true) ?? []));
        $pdo->sqliteCreateFunction('JSON_CONTAINS', function ($json, $candidate) {
            return (int) in_array(json_decode($candidate, true), json_decode($json, true) ?? []);
        }, 3);

        Schema::create('teams', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->softDeletes();
        });
        Schema::create('tags', function (Blueprint $table) {
            $table->id(); $table->string('name');
        });
        Schema::create('tag_task', function (Blueprint $table) {
            $table->unsignedBigInteger('task_id'); $table->unsignedBigInteger('tag_id');
        });
        Schema::create('task_team', function (Blueprint $table) {
            $table->unsignedBigInteger('task_id'); $table->unsignedBigInteger('team_id');
            $table->string('role'); $table->timestamps();
        });
        Schema::create('task_assignment_rules', function (Blueprint $table) {
            $table->id(); $table->string('type');
            foreach (['task_types', 'spaces', 'spaces_to', 'campuses', 'tags'] as $column) {
                $table->text($column)->nullable();
            }
            $table->softDeletes();
        });
        Schema::create('task_assignment_rule_team', function (Blueprint $table) {
            $table->unsignedBigInteger('task_assignment_rule_id'); $table->unsignedBigInteger('team_id');
        });
    }

    public function test_visibility_rules_use_current_tags_without_changing_execution_teams(): void
    {
        $this->prepareVisibilityTables();
        DB::table('teams')->insert([['id' => 2, 'name' => 'Execution'], ['id' => 3, 'name' => 'Visibility']]);
        DB::table('tags')->insert(['id' => 9, 'name' => 'Urgent']);
        DB::table('task_assignment_rules')->insert([
            'id' => 1, 'type' => 'visibility', 'task_types' => '[{"id":5}]', 'tags' => '[{"id":9}]',
        ]);
        DB::table('task_assignment_rule_team')->insert(['task_assignment_rule_id' => 1, 'team_id' => 3]);
        DB::table('tag_task')->insert(['task_id' => 1, 'tag_id' => 9]);

        $task = new Task(['id' => 1, 'task_type_id' => 5]);
        $task->exists = true;
        $task->syncExecutionTeams([2, 3]);
        $task->setRelation('tags', collect()); // Simulate a previously loaded, stale relation.
        TaskAssignmentService::syncVisibilityTeams($task);

        $this->assertEqualsCanonicalizing([2, 3], $task->executionTeams()->pluck('teams.id')->all());
        $this->assertEquals([3], $task->visibilityTeams()->pluck('teams.id')->all());

        DB::table('tag_task')->where('task_id', 1)->delete();
        TaskAssignmentService::syncVisibilityTeams($task);
        $this->assertSame([], $task->visibilityTeams()->pluck('teams.id')->all());
        $this->assertEqualsCanonicalizing([2, 3], $task->executionTeams()->pluck('teams.id')->all());
    }

    public function test_api_creation_syncs_visibility_and_rolls_back_on_failure(): void
    {
        $this->prepareVisibilityTables();
        Schema::create('tasks', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->text('description');
            $table->dateTime('start_date_time'); $table->integer('campus_id');
            $table->integer('task_type_id'); $table->integer('space_id')->nullable();
            $table->integer('space_to_id')->nullable(); $table->integer('status_id');
            $table->string('priority'); $table->integer('created_by')->nullable(); $table->timestamps();
        });
        DB::table('teams')->insert([['id' => 2, 'name' => 'Execution'], ['id' => 3, 'name' => 'Visibility']]);
        DB::table('task_assignment_rules')->insert(['id' => 1, 'type' => 'visibility', 'task_types' => '[{"id":5}]']);
        DB::table('task_assignment_rule_team')->insert(['task_assignment_rule_id' => 1, 'team_id' => 3]);
        Event::fake([BroadcastEvent::class]);

        $request = StoreTaskRequest::create('/api/v1/tasks', 'POST', ['data' => [
            'attributes' => [
                'name' => 'Test', 'description' => 'Test', 'campus_id' => 1,
                'task_type_id' => 5, 'status_id' => 1, 'priority' => 'medium',
            ],
            'relationships' => ['teams' => ['data' => [['id' => 2]]]],
        ]]);
        $controller = new TaskController();
        $this->assertSame(201, $controller->store($request)->getStatusCode());
        $this->assertEquals([2], DB::table('task_team')->where('role', 'execution')->pluck('team_id')->all());
        $this->assertEquals([3], DB::table('task_team')->where('role', 'visibility')->pluck('team_id')->all());

        Schema::drop('task_assignment_rules');
        $this->assertSame(400, $controller->store($request)->getStatusCode());
        $this->assertSame(1, DB::table('tasks')->count());
        $this->assertSame(2, DB::table('task_team')->count());
    }
}
