<?php

namespace App\Observers;

use App\Models\Task;
use App\Models\Comment;
use App\Events\BroadcastEvent;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class BroadcastObserver implements ShouldHandleEventsAfterCommit
{

    // public function created(Task|Comment $model)
    // {
    //     $eventType = strtolower(class_basename($model)) . '_created';
    //     event(new BroadcastEvent($model, $eventType));
    // }

    // public function updated(Task|Comment $model)
    // {
    //     $eventType = strtolower(class_basename($model)) . '_updated';
    //     event(new BroadcastEvent($model, $eventType));
    // }
}
