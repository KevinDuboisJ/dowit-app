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
use App\Traits\HasTeams;
use App\Traits\HasCreator;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class TaskPlanner extends Model
{
    use SoftDeletes, HasCreator, HasTeams;

    protected $casts = [
        'frequency' => TaskPlannerFrequency::class,
        'on_holiday' => ApplyOnHoliday::class,
        'action' => TaskPlannerAction::class,
        'start_date_time' => 'datetime:Y-m-d H:i', // This casts the 'next_run_at' attribute to a Carbon instance for date manipulation
        'next_run_at' => 'datetime', // This casts the 'next_run_at' attribute to a Carbon instance for date manipulation
        'interval' => Interval::class,
        'assignments' => 'array',
        'assets' => 'array',
    ];

    // Automatically handle logic during creation
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($taskPlanner) {

            if (Auth::check()) {
                // Set next_run_at to start_date_time's value if next_run_at is not set
                if (is_null($taskPlanner->next_run_at)) {
                    $taskPlanner->next_run_at = $taskPlanner->start_date_time;
                }

                $taskPlanner->created_by = Auth::id();
            }
            Cache::forget('next_run_tasks');
        });

        static::updating(function ($taskPlanner) {

            if (Auth::check()) {
                $prevStartDatetimeState = $taskPlanner->getOriginal('start_date_time');

                // Use loose comparison to check the carbon dates values
                if ($prevStartDatetimeState != $taskPlanner->start_date_time) {
                    $taskPlanner->next_run_at = $taskPlanner->start_date_time;
                }

                if ($taskPlanner->frequency === TaskPlannerFrequency::Daily->name) {
                    $taskPlanner->interval = null;
                }
                $taskPlanner->created_by = Auth::id();
            }

            Cache::forget('next_run_tasks');
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

    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id');
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

    public function getNextRunDate(?Carbon $next_run_at = null, ?string $frequency = null, array|string|null $interval = null): Carbon
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

    public function getNextWeekdayInMonth(Carbon $fromDate, int $weekNumber, string $dayOfWeek): Carbon
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

    protected function getNextSpecificDay(Carbon $currentDate, array $interval)
    {
        // Convert days to Carbon day numbers (0 = Sunday, 1 = Monday, etc.)
        $dayNumbers = array_map(function ($day) {
            return Carbon::parse($day)->dayOfWeek;
        }, $interval);

        // Sort day numbers to find the next closest
        sort($dayNumbers);

        // Check for the next available day in the list
        foreach ($dayNumbers as $dayNumber) {
            if ($currentDate->dayOfWeek <= $dayNumber) {
                return $currentDate->copy()->next($dayNumber)->setTimeFrom($currentDate);
            }
        }

        // If no upcoming days in the current week, return the first specific day in the next week
        return $currentDate->copy()->next($dayNumbers[0])->setTimeFrom($currentDate);
    }

    public function updateNextRunDate()
    {
        $nextRunDate = $this->getNextRunDate();
        $this->start_date_time = $this->next_run_at;
        $this->next_run_at = $nextRunDate->format('Y-m-d H:i:s');

        $this->save();
        Cache::forget('next_run_tasks');
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
