<?php

namespace App\Models;

use App\Contracts\HasVisibilityTeamsScopeInterface;
use App\Enums\TaskPriorityEnum;
use App\Models\Team;
use App\Models\PATIENTLIST\BedVisit;
use App\Models\PATIENTLIST\Visit;
use Illuminate\Database\Eloquent\Model;
use App\Models\TaskType;
use App\Models\Space;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Auth;
use App\Traits\HasCreator;
use App\Enums\TaskStatusEnum;
use App\Enums\TeamRole;
use App\Services\ChainService;
use App\Traits\HasAssignees;
use App\Traits\HasAccessScope;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Task extends Model implements HasVisibilityTeamsScopeInterface
{
    use HasAssignees, HasCreator, HasAccessScope;

    protected $with = ['taskType'];
    protected $appends = ['capabilities', 'is_active', 'start_date_time_with_offset'];

    protected $casts = [
        'help_requested' => 'boolean', // Cast tinyint(1) to boolean
        'start_date_time' => 'datetime',
        'priority' => TaskPriorityEnum::class,
    ];

    protected static function booted()
    {
        static::saving(function ($task) {
            // If the task is being saved without a status, determine the appropriate status based on the start date and time
            if ($task->status_id === null) {
                $task->status_id = TaskStatusEnum::fromStartDateTime($task->start_date_time)->value;
            }
        });

        static::updating(function (Task $task) {
            // Determine the current status ID, accounting for possible enum casting
            $statusId = $task->status_id instanceof TaskStatusEnum ? $task->status_id->value : $task->status_id;

            // If the task was just marked as completed, execute any related chains
            if ($task->isDirty('status_id') && (int) $statusId === TaskStatusEnum::InProgress->value && $task->started_at === null) {
                $task->started_at = now();
            }
        });

        static::updated(function (Task $task) {
            // Determine the current status ID, accounting for possible enum casting
            $statusId = $task->status_id instanceof TaskStatusEnum ? $task->status_id->value : $task->status_id;

            // If the task was just marked as completed, execute any related chains
            if ($task->wasChanged('status_id') && (int) $statusId === TaskStatusEnum::Completed->value) {
                ChainService::executeForCompletedTask($task);
            }
        });
    }

    protected function capabilities(): Attribute
    {
        return Attribute::make(
            get: fn() => [
                'can_execute' => auth()->user()?->can('execute', $this),
                'can_update'  => auth()->user()?->can('update', $this),
                'can_reject'  => auth()->user()?->can('reject', $this),
                'isAssignedToCurrentUser' => $this->assignees->contains(Auth::user()),
            ],
        );
    }

    protected function isActive(): Attribute
    {
        return Attribute::make(
            get: fn() => TaskStatusEnum::isActiveStatus($this->status_id),
        );
    }

    protected function startDateTimeWithOffset(): Attribute
    {
        return Attribute::make(
            get: function () {

                $value = $this->attributes['start_date_time'];

                if (empty($value)) {
                    return null;
                }

                $base = $value instanceof Carbon ? $value->copy() : Carbon::parse($value);
                $offset = (int) ($this->taskType?->creation_time_offset ?? 0); // minutes

                // Return the base start time with the offset applied
                return $base->addMinutes($offset);
            },
        );
    }

    public function status()
    {
        return $this->belongsTo(TaskStatus::class, 'status_id');
    }

    public function space()
    {
        return $this->belongsTo(Space::class);
    }

    public function spaceTo()
    {
        return $this->belongsTo(Space::class, 'space_to_id');
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

    public function teamRelationPath(): string
    {
        return 'executionTeams';
    }

    public function executionTeams(): BelongsToMany
    {
        return $this->belongsToMany(
            Team::class,
            'task_team',
            'task_id',
            'team_id'
        )
            ->wherePivot('role', TeamRole::Execution->value)
            ->withPivot('role')
            ->withTimestamps();
    }

    public function visibilityTeams(): BelongsToMany
    {
        return $this->belongsToMany(
            Team::class,
            'task_team',
            'task_id',
            'team_id'
        )
            ->wherePivot('role', TeamRole::Visibility->value)
            ->withPivot('role')
            ->withTimestamps();
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class)->orderBy('created_at', 'desc');
    }

    public function scopeByScheduled($query)
    {
        return $query->where('status_id', TaskStatusEnum::Scheduled->value);
    }

    public function scopeByActive($query)
    {
        return $query->whereIn('status_id', TaskStatusEnum::activeStatuses());
    }

    public function scopeByAssignedOrTeams($query)
    {
        $query->where(function ($query) {
            $query->byUserTeams()
                ->orWhere(function ($query) {
                    $query->byAssignees($query, Auth::user());
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

        // Otherwise, restrict to teams the user belongs to
        $teamIds = $user->getTeamIds();

        return $query->whereHas('executionTeams', function ($teamQuery) use ($teamIds) {
            $teamQuery->whereIn('teams.id', $teamIds);
        });
    }

    public function scopeByExecutionTeams($query, array $teamIds)
    {
        $user = Auth::user();

        return $query->where(function ($q) use ($user, $teamIds) {
            $q->whereHas('assignees', fn($sub) => $sub->whereKey($user->id))
                ->orWhereHas('executionTeams', fn($sub) => $sub->whereIn('teams.id', $teamIds));
        });
    }

    public function scopeByVisibilityTeams(Builder $query, array $teamIds): Builder
    {
        $user = Auth::user();
        
        return $query->where(function (Builder $q) use ($user, $teamIds) {
            $q->where('created_by', $user->id)
                ->orWhereHas('visibilityTeams', function (Builder $sub) use ($teamIds) {
                    $sub->whereIn('teams.id', $teamIds);
                });
        });
    }


    public function activate()
    {
        $this->update(['status_id' => TaskStatusEnum::Added]);
    }

    public function isScheduled()
    {
        return $this->status_id === TaskStatusEnum::Scheduled;
    }

    public function syncTags(array $tags): void
    {
        $this->tags()->sync($tags);
    }

    public function syncExecutionTeams(array $teamIds): void
    {
        $teamIds = ! empty($teamIds)
            ? $teamIds
            : [config('app.system_team_id')];

        $this->executionTeams()->syncWithPivotValues($teamIds, [
            'role' => TeamRole::Execution->value,
        ]);
    }

    public function syncVisibilityTeams(array $teamIds): void
    {
        $this->visibilityTeams()->syncWithPivotValues($teamIds, [
            'role' => TeamRole::Visibility->value,
        ]);
    }

    public static function getRelationships()
    {
        return [
            'visit' => fn($query) => $query->with(['patient', 'bed.room']),
            'tags',
            'status',
            'taskType' => fn($query) => $query->with(['assets']),
            'space',
            'assignees',
            'executionTeams' => fn($query) => $query->select('teams.id', 'teams.name'),
            'visibilityTeams' => fn($query) => $query->select('teams.id', 'teams.name'),
        ];
    }
}
