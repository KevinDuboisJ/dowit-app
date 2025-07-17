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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BroadcastEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private Task|Comment  $model;
    private string $eventType;
    private string $source;
    private array $extraKeys;

    /**
     * Create a new event instance.
     */
    public function __construct(Task|Comment $model, $eventType, $source, $extraKeys = [])
    {
        $this->model = $model;
        $this->eventType = $eventType;
        $this->source = $source;
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
                $this->model->assignees->map(fn($user) => new PrivateChannel("user.{$user->id}"))->toArray(),
                !empty($this->extraKeys['usersToUnassign']) ? array_map(fn($userId) => new PrivateChannel("user.{$userId}"), $this->extraKeys['usersToUnassign']) : [] // Broadcast to users that were unassigned to remove the task from their list
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
        $payload = [
            'id' => Str::uuid(), // This makes sure every event has a unique id
            'source' => $this->source, // This makes sure every event has a unique id
            'type' => $this->eventType,
            'timestamp' => $this->model->updated_at->toIso8601ZuluString(),
            'data' => ['id' => $this->model->id],
            'createdBy' => Auth::user()->id ?? config('app.system_team_id'),
            // 'broadcasted_channels' => collect($this->broadcastOn())->map(fn($channel) => (string) $channel)->toArray(),
        ]; 

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
