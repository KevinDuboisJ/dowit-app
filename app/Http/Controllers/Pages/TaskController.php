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
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

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

    return response()->json(
      Comment::with([
        'creator' => fn($q) => $q->withoutGlobalScopes(),
        'status'  => fn($q) => $q->select('id', 'name'),
      ])
        ->where('task_id', $id)
        ->orderByDesc('created_at')
        ->get()
    );
  }

  public function store(StoreTaskRequest $request, TaskService $taskService)
  {
    try {
      $data = $request->prepareForDatabase();
      $task = $taskService->create($data);

      if (! $task->isScheduled()) {
        broadcast(new BroadcastEvent($task, 'task_created', 'dashboard'));
      }

      return redirect()->back();
    } catch (\Throwable $e) {
      logger()->error('Task creation failed', [
        'message' => $e->getMessage(),
        'file'    => $e->getFile(),
        'line'    => $e->getLine(),
        'trace'   => $e->getTraceAsString(),
        'code'    => $e->getCode(),
      ]);

      return back()->withErrors([
        'message' => 'Gelieve dit te melden bij de helpdesk: #' . $request->attributes->get('log_id'),
      ]);
    }
  }

  public function update(UpdateTaskRequest $request, TaskService $taskService, Task $task): RedirectResponse
  {
    try {
      $result = $taskService->updateTask($task, $request->prepareForDatabase());

      if (isset($result['conflict'])) {
        return back()->withErrors([
          'error' => $result['message'],
          // 'data' => $result['latestData'], // optional: so frontend can show diff
        ]);
      }

      // For Inertia partial reload: the page that executed this method must provide `tasks` prop.
      return redirect()->back();
    } catch (\Throwable $e) {
      logger()->error([
        'message' => 'Task update failed: ' . $e->getMessage(),
        'file'    => $e->getFile(),
        'line'    => $e->getLine(),
        'trace'   => $e->getTraceAsString(),
      ]);

      return back()->withErrors([
        'message' => 'Gelieve dit te melden bij de helpdesk: #' . $request->attributes->get('log_id'),
      ]);
    }
  }

  public function requestHelp(Request $request, Task $task, TaskService $taskService)
  {
    try {
      $this->authorize('update', $task);

      if (! $task->needs_help) {
        $task = $task->forceFill(['needs_help' => true]);
        $task->comments()->create([
          'needs_help' => $task->isDirty('needs_help') ? $task->needs_help : null,
          'metadata' => $taskService->trackTaskMetaDataChanges($task, ['needs_help' => true]),
        ]);

        $task->save();
        broadcast(new BroadcastEvent($task, 'task_updated', 'dashboard'));
      }

      return redirect()->back();
    } catch (\Exception $e) {
      logger()->error(
        [
          'message' => 'Help request failed: ' . $e->getMessage(),
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
