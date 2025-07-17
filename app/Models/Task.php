<?php

namespace App\Models;

use App\Models\PATIENTLIST\BedVisit;
use App\Models\PATIENTLIST\Visit;
use Illuminate\Database\Eloquent\Model;
use App\Models\TaskType;
use App\Models\TaskStatus;
use App\Models\Space;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Carbon;
use App\Traits\HasTeams;
use App\Traits\HasCreator;
use App\Enums\TaskStatus as EnumsTaskStatus;

class Task extends Model
{
    use HasCreator, HasTeams;

    // protected function serializeDate(\DateTimeInterface $date): string
    // {
    //     return Carbon::parse($date)->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s');
    // }

    protected $appends = ['capabilities'];

    protected $casts = [
        'needs_help' => 'boolean', // Cast tinyint(1) to boolean
        'start_date_time' => 'datetime', // Cast tinyint(1) to boolean
    ];

    protected static function boot()
    {
        parent::boot();

        // Listen for the `attached` event on the assignees relationship
        Event::listen('eloquent.attached: App\\Models\\Task.assignees', function ($task, $ids, $attributes) {
            logger('Attached: ' . implode(',', $ids) . ' to Task ' . $task->id);
        });
    }

    // Set the default task status, or mark it as scheduled if start_date_time is in the future
    protected static function booted()
    {
        static::saving(function ($task) {
            if ($task->start_date_time > Carbon::now()) {
                $task->status_id = EnumsTaskStatus::Scheduled;
            } elseif (! $task->status) {
                $task->status_id = EnumsTaskStatus::Added;
            }
        });
    }

    public function status()
    {
        return $this->belongsTo(TaskStatus::class, 'status_id');
    }

    public function space()
    {
        return $this->belongsTo(Space::class);
    }

    public function taskType()
    {
        return $this->belongsTo(TaskType::class);
    }

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function bedVisit()
    {
        return $this->belongsTo(BedVisit::class);
    }

    public function teams()
    {
        return $this->belongsToMany(Team::class, 'task_team');
    }

    public function assignees()
    {
        return $this->belongsToMany(User::class, 'task_user');
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class)->orderBy('created_at', 'desc');
    }

    public function scopeByActive($query)
    {
        return $query->whereNotIn('status_id', [EnumsTaskStatus::Scheduled, EnumsTaskStatus::Completed]);
    }

    protected function capabilities(): Attribute
    {
        return Attribute::make(
            get: fn() => [
                'can_update' => auth()->user()?->can('update', $this),
                'can_assign' => auth()->user()?->can('assign', $this),
                'isAssignedToCurrentUser' => auth()->user()?->can('isAssignedToCurrentUser', $this),
            ],
        );
    }

    public function scopeByAssignedOrTeams($query)
    {
        $query->where(function ($query) {
            $query->byUserTeams()
                ->orWhere(function ($query) {
                    $query->byAssigned();
                });
        });
    }

    public function scopeByUserTeams($query)
    {
        $user = Auth::user();

        // If the user is super admin, don't constrain the query.
        if ($user->isSuperAdmin()) {
            return $query;
        }

        // Otherwise, restrict to teams the user belongs to using getTeams().
        $teamIds = $user->getTeams()->pluck('id');

        return $query->whereHas('teams', function ($teamQuery) use ($teamIds) {
            $teamQuery->whereIn('teams.id', $teamIds);
        });
    }

    public function scopeByNotAssigned($query)
    {
        $query->whereDoesntHave('assignees', function ($userQuery) {
            $userQuery->where('users.id', Auth::id());
        });
    }

    public function scopeByAssigned($query)
    {
        $user = Auth::user();

        $query->whereHas('assignees', function ($userQuery) use ($user) {
            $userQuery->where('users.id', $user->id);
        });
    }

    public function activate()
    {
        $this->update(['status_id' => EnumsTaskStatus::Added]);
    }

    public function isScheduled()
    {
        return $this->status_id === EnumsTaskStatus::Scheduled;
    }

    public static function getRelationships()
    {
        return [
            'visit' => fn($query) => $query->with(['patient', 'room', 'bed']),
            'tags',
            'status',
            'taskType' => fn($query) => $query->with(['assets']),
            'space',
            'assignees',
            'teams' => fn($query) => $query->select('teams.id', 'teams.name'),
        ];
    }

    public function assignUsers(array $userIds)
    {
        $this->assignees()->syncWithoutDetaching($userIds);
    }

    public function unassignUsers(array $userIds)
    {
        $this->assignees()->detach($userIds);
    }

    public function addComment(string $comment, ?array $metadata = null): Comment
    {
        $comment = $this->comments()->create([
            'created_by' => Auth::id() ?? config('app.system_user_id'),
            'status_id' => $this->isDirty('status_id') ? $this->status_id : null,
            'needs_help' => $this->isDirty('needs_help') ? $this->needs_help : null,
            'content' => $comment ?? '',
            'metadata' => !empty($metadata) ? $metadata : null,
        ]);

        return $comment;
    }
}
