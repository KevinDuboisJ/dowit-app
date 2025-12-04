<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use App\Models\Task;
use App\Enums\TaskPlannerFrequency;
use InvalidArgumentException;
use App\Enums\ApplyOnHoliday;
use App\Enums\TaskPlannerAction;
use Illuminate\Support\Facades\Cache;
use App\Casts\Interval;
use App\Models\PATIENTLIST\Visit;
use App\Traits\HasTeams;
use App\Traits\HasCreator;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use App\Services\TaskPlannerService;
use App\Traits\HasJsonAssignees;
use App\Traits\HasAccessScope;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class TaskPlanner extends Model
{
    use SoftDeletes, HasCreator, HasTeams, HasJsonAssignees, HasAccessScope;

    protected $casts = [
        'frequency' => TaskPlannerFrequency::class,
        'on_holiday' => ApplyOnHoliday::class,
        'action' => TaskPlannerAction::class,
        'next_run_at' => 'datetime', // This casts the 'next_run_at' attribute to a Carbon instance for date manipulation
        'interval' => Interval::class,
        'assignments' => 'array',
        'assets' => 'array',
        'excluded_dates' => 'array',
    ];

    // Automatically handle logic during creation
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($taskPlanner) {

            if (Auth::check()) {
                // Set next_run_at to next_run_at's value if next_run_at is not set
                if (is_null($taskPlanner->next_run_at)) {
                    $taskPlanner->next_run_at = $taskPlanner->next_run_at;
                }

                $taskPlanner->created_by = Auth::id();
            }

            Cache::forget(TaskPlannerService::getCacheKey());
        });

        static::updating(function ($taskPlanner) {
            if (Auth::check()) {

                $prevStartDatetimeState = $taskPlanner->getOriginal('next_run_at');

                // Use loose comparison to check the carbon dates values
                if ($prevStartDatetimeState != $taskPlanner->next_run_at) {
                    $taskPlanner->next_run_at = $taskPlanner->next_run_at;
                }

                if ($taskPlanner->frequency === TaskPlannerFrequency::Daily->name) {
                    $taskPlanner->interval = null;
                }
            }

            Cache::forget(TaskPlannerService::getCacheKey());
        });
    }

    public function campus()
    {
        return $this->belongsTo(Campus::class);
    }

    public function taskType()
    {
        return $this->belongsTo(TaskType::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'task_planner_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'task_user');
    }

    public function space()
    {
        return $this->belongsTo(Space::class, 'space_id');
    }

    public function spaceTo()
    {
        return $this->belongsTo(Space::class, 'space_to_id');
    }

    public function assets()
    {
        return $this->belongsToMany(Asset::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function scopeByActive($query)
    {
        return $query->where('is_active', true);
    }

    // Deprecated
    // public function scopeByWithinWindow($query, Carbon $now)
    // {
    //     return $query->whereRaw('DATE_SUB(next_run_at, INTERVAL task_types.creation_time_offset MINUTE) <= ?', [$now]);
    // }

    public function getNextRunDate(Carbon|CarbonImmutable|null $next_run_at = null, ?string $frequency = null, array|string|null $interval = null): Carbon|CarbonImmutable
    {
        // Fallback to the model's properties if no arguments are passed
        $next_run_at = $next_run_at ?? $this->next_run_at;
        $frequency = $frequency ?? $this->frequency->name;
        $interval = $interval ?? $this->interval;

        // The copy() method on the Carbon instance ensures that a new instance of the date is returned after adding the day/week/month.
        // This avoids mutating the original next_run_at value stored in the model.

        switch ($frequency) {
            case 'Daily':
                return $next_run_at->copy()->addDay();
                break;

            case 'Weekly':
                return $next_run_at->copy()->addWeek();
                break;

            case 'Monthly':
                return $next_run_at->copy()->addMonth();
                break;

            case 'Quarterly':
                return $next_run_at->copy()->addMonths(3);
                break;

            case 'EachXDay':
                if (!$interval) {
                    throw new InvalidArgumentException("Day interval must be provided for 'EachXDay'.");
                }
                return $next_run_at->copy()->addDays((int) $interval);

            case 'SpecificDays':

                if (!$interval) {
                    throw new InvalidArgumentException("Specific days must be provided for 'interval'.");
                }

                // Find the next occurrence of one of the specific days
                return $this->getNextSpecificDay($next_run_at, $interval);

            case 'Weekdays':
                // If it's Fri, this will jump to Monday; if Tue, it goes to Wed, etc.
                return $next_run_at->copy()->nextWeekday();

            case 'WeekdayInMonth':
                if (
                    !is_array($interval) ||
                    !isset($interval['week_number']) ||
                    !isset($interval['day_of_week'])
                ) {
                    throw new InvalidArgumentException("Interval must contain 'week_number' and 'day_of_week' for 'WeekdayInMonth'.");
                }

                return $this->getNextWeekdayInMonth($next_run_at, $interval['week_number'], $interval['day_of_week']);

            default:
                throw new InvalidArgumentException("Invalid frequency: $frequency");
        }
    }

    public function getNextWeekdayInMonth(Carbon|CarbonImmutable $fromDate, int $weekNumber, string $dayOfWeek): Carbon
    {
        // Store original time
        $hour = $fromDate->hour;
        $minute = $fromDate->minute;
        $second = $fromDate->second;

        $date = $fromDate->copy()->startOfMonth();
        $count = 0;

        while (true) {
            if (strtolower($date->format('l')) === strtolower($dayOfWeek)) {
                $count++;
                if ($count === $weekNumber) {
                    if ($date->greaterThan($fromDate)) {
                        return $date->setTime($hour, $minute, $second);
                    }
                    break;
                }
            }
            $date->addDay();
            if ($date->month !== $fromDate->month) {
                break;
            }
        }

        // Next month
        $nextMonth = $fromDate->copy()->addMonth()->startOfMonth();
        $count = 0;
        while (true) {
            if (strtolower($nextMonth->format('l')) === strtolower($dayOfWeek)) {
                $count++;
                if ($count === $weekNumber) {
                    return $nextMonth->setTime($hour, $minute, $second);
                }
            }
            $nextMonth->addDay();
        }
    }

    protected function getNextSpecificDay(Carbon|CarbonImmutable $currentDate, array $interval)
    {
        // Convert days to Carbon day numbers (0 = Sunday, 1 = Monday, etc.)
        $dayNumbers = array_map(function ($day) {
            return Carbon::parse($day)->dayOfWeek;
        }, $interval);

        // Sort day numbers to find the next closest
        sort($dayNumbers);

        // Check for the next available day in the list
        foreach ($dayNumbers as $dayNumber) {
            if ($currentDate->dayOfWeek < $dayNumber) {
                return $currentDate->copy()->next($dayNumber)->setTimeFrom($currentDate);
            }
        }

        // If no upcoming days in the current week, return the first specific day in the next week
        return $currentDate->copy()->next($dayNumbers[0])->setTimeFrom($currentDate);
    }

    public function activate(): bool
    {
        return $this->update(['is_active' => true]);
    }

    public function deactivate(): bool
    {
        return $this->update(['is_active' => false]);
    }

    public function getOffsetRunAt(): Carbon
    {
        return Carbon::parse($this->next_run_at)
            ->subMinutes($this->taskType?->creation_time_offset ?? 0);
    }

    public function updateNextRunDate()
    {
        $this->next_run_at = $this->getNextRunDate();
        $this->save();
        Cache::forget(TaskPlannerService::getCacheKey());
    }

    public function nextRunAtIsExcluded(): bool
    {
        if (empty($this->excluded_dates)) {
            return false;
        }

        $nextRunDate = Carbon::parse($this->next_run_at)->toDateString();

        if (in_array($nextRunDate, $this->excluded_dates, true)) {
            return true;
        }

        return false;
    }

    public function toTaskModel(): Task
    {
        $task = new Task([
            'campus_id'    => $this->campus_id,
            'task_type_id' => $this->task_type_id,
            'space_id'     => $this->space_id,
            'space_to_id'  => $this->space_to_id,
        ]);
        $task->setRelation('tags', $this->tags);
        return $task;
    }
}