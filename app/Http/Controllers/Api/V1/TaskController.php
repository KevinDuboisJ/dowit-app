<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreTaskRequest;
use App\Models\Task;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use App\Events\BroadcastEvent;

class TaskController extends Controller
{
    public function store(StoreTaskRequest $request)
    {
        try {
            $task = DB::transaction(function () use ($request) {
                $task = Task::create([
                    'start_date_time' => Carbon::now(),
                    'name' => $request->input('data.attributes.name'),
                    'description' => $request->input('data.attributes.description'),
                    'campus_id' => $request->input('data.attributes.campus_id'),
                    'task_type_id' => $request->input('data.attributes.task_type_id'),
                    'space_id' => $request->input('data.attributes.space_id'),
                    'space_to_id' => $request->input('data.attributes.space_to_id'),
                    'status_id' => $request->input('data.attributes.status_id'),
                    'priority' => $request->input('data.attributes.priority'),
                ]);

                // Extract team IDs from the nested JSON API structure.
                $teamIds = collect($request->input('data.relationships.teams.data'))
                    ->pluck('id')
                    ->toArray();

                // Sync the teams with the task.
                $task->teams()->sync($teamIds);

                return $task;
            });
        } catch (\Exception $e) {
            // Optionally log the exception.
            logger('Error creating task: ' . $e->getMessage());

            return response()->json([
                'message' => 'Failed to create task',
                'error'   => $e->getMessage(),
            ], 400);
        }

        broadcast(new BroadcastEvent($task, 'task_created', 'dashboard'));

        return response()->json([
            'message' => 'Task created successfully',
            // Optionally, include data like the created task if needed:
            // 'data' => new TaskResource($task),
        ], 201);
    }
}
