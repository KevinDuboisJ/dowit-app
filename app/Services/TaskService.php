<?php

namespace App\Services;

use App\Models\Task;
use App\Models\User;
use App\Models\Comment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use App\Enums\TaskStatus;
use App\Events\BroadcastEvent;
use Illuminate\Support\Facades\Cache;

class TaskService
{
  public function updateTask(Task $task, array $data)
  {
    $clientTimestamp = Carbon::parse($data['beforeUpdateAt']);
    
    if ($clientTimestamp->lt($task->updated_at)) {
      return [
        'conflict' => true,
        'message' => 'Het bijwerken van de taak is mislukt omdat deze onlangs al is bijgewerkt',
        'latestData' => $task->load(Task::getRelationships()),
      ];
    }

    DB::transaction(function () use ($task, $data) {

      $task->fill(Arr::only($data, [
        'status_id',
        'priority',
        'needs_help',
        'updated_at',
      ]));
      
      $metadata = $this->trackMetadataChanges($task);
      $this->handleUserAssignments($task, $data, $metadata);
      $comment = $this->createTaskComment($task, $data, $metadata);
      $this->handleAutoStatusReset($task, $data, $comment);
      $task->save();
    });
  
    $task->load(Task::getRelationships());
    Cache::put("task_{$task->id}", $task, now()->addMinutes(3));
    broadcast(new BroadcastEvent($task, 'task_updated'));

    return [
      'success' => true,
      'task' => $task,
    ];
  }

  private function trackMetadataChanges(Task $task): array
  {
    $metadata = [];

    if ($task->isDirty('status_id')) {
      $metadata['changed_keys']['status'] = $task->status->name;
    }

    if ($task->isDirty('priority')) {
      $metadata['changed_keys']['priority'] = $task->priority;
    }

    if ($task->isDirty('needs_help')) {
      $metadata['changed_keys']['needs_help'] = $task->needs_help;
    }

    return $metadata;
  }

  private function handleUserAssignments(Task $task, array $data, array &$metadata): void
  {
    if (!empty($data['usersToAssign'])) {
      $task->assignUsers($data['usersToAssign']);
      $metadata['changed_keys']['assignees'] = User::whereIn('id', $data['usersToAssign'])
      ->pluck(DB::raw("CONCAT(firstname, ' ', lastname)"))
      ->toArray();
    }

    if (!empty($data['usersToUnassign'])) {
      $task->unassignUsers($data['usersToUnassign']);
      $metadata['changed_keys']['unassignees'] = User::whereIn('id', $data['usersToUnassign'])
      ->pluck(DB::raw("CONCAT(firstname, ' ', lastname)"))
      ->toArray();
    }
  }

  private function createTaskComment(Task $task, array $data, array $metadata): Comment
  {
    $comment = $task->comments()->create([
      'user_id' => Auth::id(),
      'status_id' => $task->isDirty('status_id') ? $task->status_id : null,
      'needs_help' => $task->isDirty('needs_help') ? $task->needs_help : null,
      'content' => $data['comment'] ?? '',
      'metadata' => !empty($metadata) ? $metadata : null,
    ]);

    return $comment;
  }

  private function handleAutoStatusReset(Task $task, array $data, Comment $comment): void
  {
    if ($task->assignedUsers->isEmpty() && !empty($data['usersToUnassign'])) {
      $task->update(['status_id' => TaskStatus::Added->value]);

      $task->comments()->create([
        'user_id' => config('app.system_user_id'),
        'content' => 'Status werd automatisch omgezet naar Toegevoegd',
        'created_at' => $comment->created_at->addSecond(),
      ]);

      $task->load('comments');
    }
  }
}
