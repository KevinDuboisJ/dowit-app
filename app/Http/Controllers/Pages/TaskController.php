<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Models\PATIENTLIST\Room;
use App\Models\PATIENTLIST\PatientRoom;
use App\Models\PATIENTLIST\Patient;
use App\Services\TaskAssignmentService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Events\BroadcastEvent;
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
    $task = Cache::get("task_{$id}");

    // If not in cache, fetch from the database and optionally cache it
    if (!$task) {

      // Retrieve the relationships from the getRelationships method
      $relationships = Task::getRelationships();

      // Pass the relationships to the with() method
      $task = Task::with($relationships)->findOrFail($id);

      // Cache for 3 minutes
      Cache::put("task_{$id}", $task, now()->addMinutes(3));
    }

    return response()->json($task);
  }

  public function store(StoreTaskRequest $request)
  {
    try {

      $data = $request->prepareForDatabase();

      // Start transactions on both connections
      DB::connection('mysql')->beginTransaction();

      if (isset($data['patient'])) {

        DB::connection('patientlist')->beginTransaction();

        // Create the Room if doesn't exist
        $room = Room::firstOrCreate(
          ['number' => $data['patient']['room_id']],
          ['number' => $data['patient']['room_id']]
        );

        // Create or Update the Patient
        $patient = Patient::updateOrCreate(
          ['patient_id' => $data['patient']['pat_id']], // Find by API patient ID
          [
            'visit_id' => $data['patient']['visit_id'],
            'firstname' => $data['patient']['firstname'],
            'lastname' => $data['patient']['lastname'],
            'gender' => $data['patient']['gender'],
            'birthdate' => $data['patient']['birthdate'],
            'ext_id_1' => $data['patient']['ext_id_1'],
            'campus_id' => $data['patient']['campus_id'],
            'ward_id' => $data['patient']['ward_id'],
            'room_id' => $room->id, // Link the current room
            'bed_id' => $data['patient']['bed_id'],
            'admission' => $this->mergeDateTime($data['patient']['adm_date'], $data['patient']['adm_time']),
            'discharge' => $this->mergeDateTime($data['patient']['dis_date'], $data['patient']['dis_time']),
          ]
        );

        // Check and Update Room-Patient Relationship
        $latestRoomRecord = PatientRoom::where('patient_id', $patient->id)
          ->latest('created_at')
          ->first();

        if (!$latestRoomRecord || $latestRoomRecord->room_id !== $room->id) {
          // If there's no record or the room has changed, add a new record
          PatientRoom::create([
            'patient_id' => $patient->id,
            'room_id' => $room->id,
          ]);
        }
        // Create the task with validated data
        $task = new Task([...$data['task'], 'patient_id' => $patient->id]);
      }

      if (!isset($patient)) {
        $task = new Task($data['task']);
      }

      $task->deactivateIfScheduledForFuture();
      $task->save();


      // Sync the many-to-many relationship for assigned users
      if ($request->validated('assignTo')) {
        // Extract ids from array
        $ids = array_column($request->validated('assignTo'), 'value');
        $task->assignees()->sync($ids);
      }

      TaskAssignmentService::assignTaskToTeams($task);

      // Commit the transaction if all operations succeed
      DB::connection('mysql')->commit();

      if (isset($patient)) {
        DB::connection('patientlist')->commit();
      }

      $task->load(Task::getRelationships());

      if ($task->is_active) {
        broadcast(new BroadcastEvent($task, 'task_created', 'dashboard'));
      }

      return response()->json($task);
    } catch (\Throwable $e) {

      // Log the error for debugging purposes
      logger()->error('Task creation failed', [
        'error' => $e->getMessage(),
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
      logger()->error('Task update failed', ['error' => $e->getMessage()]);
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

  private static function getTasksTemplate($task)
  {
    return Task::with(Task::getRelationships())
      ->byAssignedOrTeams()
      ->where('id', $task->id)
      ->get()
      ->map(function ($task) {
        return array_merge(
          $task->toArray(),
          [
            'capabilities' => [
              'can_update' => Auth::user()->can('update', $task),
              'can_assign' => Auth::user()->can('assign', $task),
              'isAssignedToCurrentUser' => Auth::user()->can('isAssignedToCurrentUser', $task),
            ],
          ]
        );
      });
  }
}
