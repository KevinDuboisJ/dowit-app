<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Services\TaskAssignmentService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Events\BroadcastEvent;
use App\Models\Comment;
use Illuminate\Support\Facades\Cache;
use App\Services\TaskService;

class TaskController extends Controller
{
  protected TaskService $taskService;

  public function __construct(TaskService $taskService)
  {
    $this->taskService = $taskService;
  }

  public function find($id)
  {
    // Attempt to fetch the task from the cache
    $task = Cache::get("task:{$id}");

    // If not in cache, fetch from the database and optionally cache it
    if (!$task) {

      // Retrieve the relationships from the getRelationships method
      $relationships = Task::getRelationships();

      // Pass the relationships to the with() method
      $task = Task::with($relationships)->findOrFail($id);

      // Cache for 3 minutes
      Cache::put("task:{$id}", $task, now()->addMinutes(3));
    }

    return response()->json($task);
  }

  public function comments($id)
  {
    if (!request()->expectsJson()) {
      abort(404);
    }

    return response()->json(Comment::with(['creator', 'status' => fn($query) => $query->select('id', 'name')])->where('task_id', $id)->orderBy('created_at', 'desc')->get());
  }

  public function store(StoreTaskRequest $request, TaskService $taskService)
  {
    try {
      $data = $request->prepareForDatabase();
      $task = $taskService->create($data);

      if (! $task->isScheduled()) {
        broadcast(new BroadcastEvent($task, 'task_created', 'dashboard'));
      }

      return response()->json($task);
      
    } catch (\Throwable $e) {
      logger()->error('Task creation failed', [
        'message' => $e->getMessage(),
        'file'    => $e->getFile(),
        'line'    => $e->getLine(),
        'trace'   => $e->getTraceAsString(),
        'code'    => $e->getCode(),
      ]);

      return response()->json([
        'message' => 'Gelieve dit te melden bij de helpdesk: #' . $request->attributes->get('log_id'),
      ], 422);
    }
  }

  public function update(UpdateTaskRequest $request, TaskService $taskService, Task $task)
  {
    try {
      $result = $this->taskService->updateTask($task, $request->prepareForDatabase());
      $tasks = $taskService->fetchAndCombineTasks($request);

      if (isset($result['conflict'])) {
        return response()->json([
          'message' => $result['message'],
          'data' => $result['latestData'],
        ], 409);
      }

      return response()->json(['data' => $tasks, 'updatedTask' => $result['task']]);
    } catch (\Exception $e) {
      logger()->error(
        [
          'message' => 'Task update failed: ' . $e->getMessage(),
          'file'    => $e->getFile(),
          'line'    => $e->getLine(),
          'trace'   => $e->getTraceAsString(),
        ]
      );

      return response()->json([
        'message' => 'Gelieve dit te melden bij de helpdesk: #' . $request->attributes->get('log_id'),
      ], 422);
    }
  }

  public function mergeDateTime($date, $time)
  {
    // Handle null values
    $datePart = $date ? Carbon::parse($date)->toDateString() : null;
    $timePart = $time ? Carbon::parse($time)->toTimeString() : null;

    // Determine the merged datetime
    if ($datePart && $timePart) {
      $mergedDateTime = Carbon::parse("$datePart $timePart");
    } elseif ($datePart) {
      $mergedDateTime = Carbon::parse("$datePart 00:00:00");
    } elseif ($timePart) {
      $mergedDateTime = Carbon::parse("1970-01-01 $timePart");
    } else {
      $mergedDateTime = null; // Both are null
    }
    return $mergedDateTime;
  }
}
