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
use App\Services\PatientService;

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

    return response()->json( Comment::with(['creator', 'status' => fn($query) => $query->select('id', 'name')])->where('task_id', $id)->orderBy('created_at', 'desc')->get());
  }

  public function store(StoreTaskRequest $request)
  {
    try {
      $user = Auth::user();
      $data = $request->prepareForDatabase();

      // Start transactions on both connections
      DB::connection('mysql')->beginTransaction();

      if (isset($data['patient'])) {

        DB::connection('patientlist')->beginTransaction();

        $data['patient']['admission'] = $this->mergeDateTime($data['patient']['adm_date'], $data['patient']['adm_time']);
        $data['patient']['discharge'] = $this->mergeDateTime($data['patient']['dis_date'], $data['patient']['dis_time']);

        $visit = PatientService::createOrUpdateVisit($data['patient']);

        // Create the task with validated data
        $task = new Task([...$data['task'], 'visit_id' => $visit->id]);
      }

      if (!isset($visit)) {
        $task = new Task($data['task']);
      }

      // Save in db
      $task->save();

      // Sync tags
      $task->tags()->sync(collect($data['tags'] ?? [])->pluck('value'));

      // Sync the many-to-many relationship for assigned users
      if ($request->validated('assignTo')) {
        // Extract ids from array
        $ids = array_column($request->validated('assignTo'), 'value');
        $task->assignees()->sync($ids);
      }

      TaskAssignmentService::assignTaskToTeams($task, $user->teams->pluck('id')->toArray());

      // Commit the transaction if all operations succeed
      DB::connection('mysql')->commit();

      if (isset($visit)) {
        DB::connection('patientlist')->commit();
      }

      $task->load(Task::getRelationships());

      if (!$task->isScheduled()) {
        broadcast(new BroadcastEvent($task, 'task_created', 'dashboard'));
      }

      return response()->json($task);
    } catch (\Throwable $e) {
      logger()->error('Task creation failed', [
        'message' => $e->getMessage(),
        'file'    => $e->getFile(),
        'line'    => $e->getLine(),
        'trace'   => $e->getTraceAsString(),
        // optionally more context:
        'code'    => $e->getCode(),
        // 'previous' => $e->getPrevious(), // if needed
      ]);

      // Rollback both transactions if anything fails
      DB::connection('mysql')->rollBack();
      DB::connection('patientlist')->rollBack();

      // Return a user-friendly error response
      return response()->json([
        'message' => 'Gelieve dit te melden bij de helpdesk: #' . $request->attributes->get('log_id'),
      ], 422);
    }
  }

  public function update(UpdateTaskRequest $request, TaskService $taskService, Task $task)
  {
    try {
      $result = $this->taskService->updateTask($task, $request->validated());
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
