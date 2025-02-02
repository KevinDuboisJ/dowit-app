<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Task;
use App\Models\Comment;
use Illuminate\Support\Facades\Log;

class BroadcastEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private Task|Comment  $model;
    private string $eventType;
    private array $extraKeys;

    /**
     * Create a new event instance.
     */
    public function __construct(Task|Comment $model, $eventType, $extraKeys = [])
    {
        $this->model = $model;
        $this->eventType = $eventType;
        $this->extraKeys = $extraKeys;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn()
    {
        $channels = [];

        if ($this->model instanceof Task) {
            $channels = array_merge(
                $this->model->teams->map(fn($team) => new PrivateChannel("team.{$team->id}"))->toArray(),
                $this->model->assignedUsers->map(fn($user) => new PrivateChannel("user.{$user->id}"))->toArray()
            );
        }

        if ($this->model instanceof Comment) {
            if ($this->model->recipient_users) {
                foreach ($this->model->recipient_users as $userId) {
                    $channels[] = new PrivateChannel("user.{$userId}");
                }
            }
            if ($this->model->recipient_teams) {

                foreach ($this->model->recipient_teams as $teamId) {
                    $channels[] = new PrivateChannel("team.{$teamId}");
                }
            }
        }

        return $channels;
    }

    public function broadcastWith()
    {
        $payload = ['type' => $this->eventType, 'timestamp' => $this->model->updated_at->toIso8601ZuluString()]; // Add timestamp

        // Extract the values for the extra keys from $this->model
        // $extraData = array_intersect_key(
        //     $this->model->toArray(),
        //     array_flip($this->extraKeys)
        // );

        if ($this->model instanceof Task) {

            if ($this->eventType === 'task_created') {
                $payload['data'] = ['id' => $this->model->id];
                // $payload['data'] = $this->model->fresh(Task::getRelationships())->toArray();
            }

            if ($this->eventType === 'task_updated') {
                // Include the task ID and any other relevant data in the broadcast payload
                $payload['data'] = ['id' => $this->model->id];
            }
        }

        if ($this->model instanceof Comment) {
            // Include the task ID and any other relevant data in the broadcast payload
            $payload['data'] = [
                'id' => $this->model->id,
                'content' => $this->model->content,
            ];
        }

        return $payload;
    }
}
