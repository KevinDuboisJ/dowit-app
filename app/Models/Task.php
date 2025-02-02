<?php

namespace App\Models;

use App\Models\PATIENTLIST\Patient;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\TaskType;
use App\Models\TaskStatus;
use App\Models\Space;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Carbon;

class Task extends Model
{
    use HasFactory;

    protected $appends = ['capabilities'];

    protected static function boot()
    {
        parent::boot();

        // Listen for the `attached` event on the assignedUsers relationship
        Event::listen('eloquent.attached: App\\Models\\Task.assignedUsers', function ($task, $ids, $attributes) {
            logger('Attached: ' . implode(',', $ids) . ' to Task ' . $task->id);
        });
    }

    protected $casts = [
        'needs_help' => 'boolean', // Cast tinyint(1) to boolean
        'start_date_time' => 'datetime', // Cast tinyint(1) to boolean
    ];

    // protected function serializeDate(\DateTimeInterface $date): string
    // {
    //     return Carbon::parse($date)->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s');
    // }

    public function scopeByActive($query)
    {
        $query->where(function ($query) {
            $query->where('start_date_time', '<=', carbon::now())
            ->where('is_active', true);
        });
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

        $query->whereHas('teams', function ($teamQuery) use ($user) {

            $teamQuery->whereIn('teams.id', $user->teams->pluck('id'));
        });
    }

    public function scopeByNotAssigned($query)
    {
        $query->whereDoesntHave('assignedUsers', function ($userQuery) {
            $userQuery->where('users.id', Auth::id());
        });
    }

    public function scopeByAssigned($query)
    {
        $user = Auth::user();

        $query->whereHas('assignedUsers', function ($userQuery) use ($user) {
            $userQuery->where('users.id', $user->id);
        });
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

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function teams()
    {
        return $this->belongsToMany(Team::class, 'task_team');
    }

    public function assignedUsers()
    {
        return $this->belongsToMany(User::class, 'task_user');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class)->orderBy('created_at', 'desc');
    }

    public function activate()
    {
        $this->update(['is_active' => true]);
    }

    public function deactivateIfScheduledForFuture(): void
    {
        if ($this->start_date_time >= Carbon::now()) {
            $this->is_active = false;
        }
    }

    public static function getRelationships()
    {
        return [
            'patient',
            'status',
            'taskType' => fn($query) => $query->with(['documents']),
            'space',
            'comments' => fn($query) => $query->with(['user', 'status' => fn($query) => $query->select('id', 'name')]),
            'assignedUsers',
            'teams' => fn($query) => $query->select('teams.id', 'teams.name'),
        ];
    }

    public function assignUsers(array $userIds)
    {
        $this->assignedUsers()->syncWithoutDetaching($userIds);
    }

    public function unassignUsers(array $userIds)
    {
        $this->assignedUsers()->detach($userIds);
    }

    // public function firstComment()
    // {
    //     return $this->hasOne(Comment::class, 'task_id', 'id')
    //         ->orderBy('created_at', 'asc');
    // }
}
