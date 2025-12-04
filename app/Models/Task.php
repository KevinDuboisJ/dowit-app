<?php

namespace App\Models;

use App\Enums\TaskPriorityEnum;
use App\Models\Team;
use App\Models\PATIENTLIST\BedVisit;
use App\Models\PATIENTLIST\Visit;
use Illuminate\Database\Eloquent\Model;
use App\Models\TaskType;
use App\Models\Space;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Auth;
use App\Traits\HasTeams;
use App\Traits\HasCreator;
use App\Enums\TaskStatusEnum;
use App\Traits\HasAssignees;
use App\Traits\HasAccessScope;
use Carbon\Carbon;

class Task extends Model
{
    use HasAssignees, HasCreator, HasTeams, HasAccessScope;

    protected $with = ['taskType'];
    protected $appends = ['capabilities', 'start_date_time_with_offset'];

    protected $casts = [
        'needs_help' => 'boolean', // Cast tinyint(1) to boolean
        'start_date_time' => 'datetime',
        'priority' => TaskPriorityEnum::class,
    ];

    protected static function booted()
    {
        // Set the default task status
        static::saving(function ($task) {
            if ($task->status_id === null) {
                $task->status_id = TaskStatusEnum::fromStartDateTime($task->start_date_time)->value;
            }
        });
    }

    protected function capabilities(): Attribute
    {
        return Attribute::make(
            get: fn() => [
                'can_modify' => auth()->user()?->can('modify', $this),
                'can_update' => auth()->user()?->can('update', $this),
                'can_assign' => auth()->user()?->can('assign', $this),
                'can_reject' => auth()->user()?->can('reject', $this),
                'isAssignedToCurrentUser' => auth()->user()?->can('isAssignedToCurrentUser', $this),
            ],
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

        return $query->whereHas('teams', function ($teamQuery) use ($teamIds) {
            $teamQuery->whereIn('teams.id', $teamIds);
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

    public function syncTeams(array $teams): void
    {
        if (!empty($teams))
            $this->teams()->sync($teams);
        else
            $this->teams()->attach(config('app.system_team_id'));
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
            'teams' => fn($query) => $query->select('teams.id', 'teams.name'),
        ];
    }
}
