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
        'is_active' => 'boolean',
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

    public function getNextRunDate(Carbon|CarbonImmutable|null $next_run_at = null, ?string $frequency = null, array|string|null $interval = null, bool $catchUpToToday = true): Carbon|CarbonImmutable
    {
        $next_run_at = $next_run_at ?? $this->next_run_at;
        $frequency = $frequency ?? $this->frequency->name;
        $interval = $interval ?? $this->interval;

        $date = $this->advanceRunDateOnce($next_run_at, $frequency, $interval);

        if (! $catchUpToToday) {
            return $date;
        }

        $now = $next_run_at instanceof CarbonImmutable
            ? CarbonImmutable::now($next_run_at->getTimezone())
            : Carbon::now($next_run_at->getTimezone());

        if (! $date->lt($now)) {
            return $date;
        }

        return $this->advanceRunDateToNow($date, $now, $frequency, $interval);
    }

    protected function advanceRunDateToNow(Carbon|CarbonImmutable $date, Carbon|CarbonImmutable $today, string $frequency, array|string|null $interval = null): Carbon|CarbonImmutable
    {
        return match ($frequency) {
            'Daily' => $this->catchUpByDays($date, $today, 1),
            'Weekly' => $this->catchUpByDays($date, $today, 7),
            'EachXDay' => $this->catchUpByDays(
                $date,
                $today,
                $this->validatePositiveIntInterval($interval, "Day interval must be provided for 'EachXDay'.")
            ),
            'Monthly' => $this->catchUpMonthly($date, $today, 1, false, null),
            'Quarterly' => $this->catchUpMonthly($date, $today, 3, false, null),
            'EachXMonth' => $this->catchUpEachXMonth($date, $today, $interval),

            // These are calendar-based and safer to iterate
            'SpecificDays',
            'Weekdays',
            'WeekdayInMonth' => $this->catchUpIteratively($date, $today, $frequency, $interval),

            default => throw new InvalidArgumentException("Invalid frequency: $frequency"),
        };
    }

    protected function advanceRunDateOnce(Carbon|CarbonImmutable $next_run_at, string $frequency, array|string|null $interval = null): Carbon|CarbonImmutable
    {
        switch ($frequency) {
            case 'Daily':
                return $next_run_at->copy()->addDay();

            case 'Weekly':
                return $next_run_at->copy()->addWeek();

            case 'Monthly':
                return $next_run_at->copy()->addMonth();

            case 'Quarterly':
                return $next_run_at->copy()->addMonths(3);

            case 'EachXDay':
                return $next_run_at->copy()->addDays(
                    $this->validatePositiveIntInterval($interval, "Day interval must be provided for 'EachXDay'.")
                );

            case 'EachXMonth':
                if (! $interval) {
                    throw new InvalidArgumentException("Interval must be provided for 'EachXMonth'.");
                }

                if (is_array($interval)) {
                    if (! isset($interval['months'])) {
                        throw new InvalidArgumentException("Interval array for 'EachXMonth' must contain a 'months' key.");
                    }

                    $months = (int) $interval['months'];
                    if ($months < 1) {
                        throw new InvalidArgumentException("'months' must be greater than 0 for 'EachXMonth'.");
                    }

                    $noOverflow = array_key_exists('no_overflow', $interval)
                        ? (bool) $interval['no_overflow']
                        : true;

                    $anchorDay = $interval['day_of_month'] ?? null;

                    $date = $noOverflow
                        ? $next_run_at->copy()->addMonthsNoOverflow($months)
                        : $next_run_at->copy()->addMonths($months);

                    if ($anchorDay !== null) {
                        $date = $this->applyAnchorDay($date, $anchorDay);
                    }

                    return $date;
                }

                $months = (int) $interval;
                if ($months < 1) {
                    throw new InvalidArgumentException("Interval must be greater than 0 for 'EachXMonth'.");
                }

                return $next_run_at->copy()->addMonthsNoOverflow($months);

            case 'SpecificDays':
                if (! $interval) {
                    throw new InvalidArgumentException("Specific days must be provided for 'interval'.");
                }

                return $this->getNextSpecificDay($next_run_at, $interval);

            case 'Weekdays':
                return $next_run_at->copy()->nextWeekday();

            case 'WeekdayInMonth':
                if (
                    ! is_array($interval) ||
                    ! isset($interval['week_number']) ||
                    ! isset($interval['day_of_week'])
                ) {
                    throw new InvalidArgumentException("Interval must contain 'week_number' and 'day_of_week' for 'WeekdayInMonth'.");
                }

                return $this->getNextWeekdayInMonth(
                    $next_run_at,
                    $interval['week_number'],
                    $interval['day_of_week']
                );

            default:
                throw new InvalidArgumentException("Invalid frequency: $frequency");
        }
    }

    protected function catchUpByDays(Carbon|CarbonImmutable $date, Carbon|CarbonImmutable $now, int $stepDays): Carbon|CarbonImmutable
    {
        if ($stepDays < 1) {
            throw new InvalidArgumentException('Step days must be greater than 0.');
        }

        if (! $date->lt($now)) {
            return $date;
        }

        $secondsDiff = $date->diffInSeconds($now, false);

        if ($secondsDiff <= 0) {
            return $date;
        }

        $stepSeconds = $stepDays * 86400;
        $jumps = (int) ceil($secondsDiff / $stepSeconds);

        return $date->copy()->addDays($jumps * $stepDays);
    }

    protected function catchUpMonthly(Carbon|CarbonImmutable $date, Carbon|CarbonImmutable $now, int $stepMonths, bool $noOverflow = false, ?int $anchorDay = null): Carbon|CarbonImmutable
    {
        if ($stepMonths < 1) {
            throw new InvalidArgumentException('Step months must be greater than 0.');
        }

        if (! $date->lt($now)) {
            return $date;
        }

        $monthsDiff = (($now->year - $date->year) * 12) + ($now->month - $date->month);
        $jumps = max(1, (int) floor($monthsDiff / $stepMonths));

        $candidate = $this->addMonthsSafely($date, $jumps * $stepMonths, $noOverflow, $anchorDay);

        while ($candidate->lt($now)) {
            $candidate = $this->addMonthsSafely($candidate, $stepMonths, $noOverflow, $anchorDay);
        }

        return $candidate;
    }

    protected function catchUpEachXMonth(Carbon|CarbonImmutable $date, Carbon|CarbonImmutable $now, array|string|null $interval): Carbon|CarbonImmutable
    {
        if (! $interval) {
            throw new InvalidArgumentException("Interval must be provided for 'EachXMonth'.");
        }

        if (is_array($interval)) {
            if (! isset($interval['months'])) {
                throw new InvalidArgumentException("Interval array for 'EachXMonth' must contain a 'months' key.");
            }

            $months = (int) $interval['months'];
            if ($months < 1) {
                throw new InvalidArgumentException("'months' must be greater than 0 for 'EachXMonth'.");
            }

            $noOverflow = array_key_exists('no_overflow', $interval)
                ? (bool) $interval['no_overflow']
                : true;

            $anchorDay = isset($interval['day_of_month']) ? (int) $interval['day_of_month'] : null;

            return $this->catchUpMonthly($date, $now, $months, $noOverflow, $anchorDay);
        }

        $months = (int) $interval;
        if ($months < 1) {
            throw new InvalidArgumentException("Interval must be greater than 0 for 'EachXMonth'.");
        }

        return $this->catchUpMonthly($date, $now, $months, true, null);
    }

    protected function catchUpIteratively(Carbon|CarbonImmutable $date, Carbon|CarbonImmutable $now, string $frequency, array|string|null $interval = null): Carbon|CarbonImmutable
    {
        while ($date->lt($now)) {
            $date = $this->advanceRunDateOnce($date, $frequency, $interval);
        }

        return $date;
    }

    protected function addMonthsSafely(Carbon|CarbonImmutable $date, int $months, bool $noOverflow = true, int $anchorDay): Carbon|CarbonImmutable
    {
        $result = $noOverflow
            ? $date->copy()->addMonthsNoOverflow($months)
            : $date->copy()->addMonths($months);

        if ($anchorDay !== null) {
            $result = $this->applyAnchorDay($result, $anchorDay);
        }

        return $result;
    }

    protected function applyAnchorDay(Carbon|CarbonImmutable $date, int $anchorDay,): Carbon|CarbonImmutable
    {
        if ($anchorDay < 1 || $anchorDay > 31) {
            throw new InvalidArgumentException("'day_of_month' must be between 1 and 31 for 'EachXMonth'.");
        }

        $lastDayOfMonth = $date->copy()->endOfMonth()->day;

        return $date->copy()->day(min($anchorDay, $lastDayOfMonth));
    }

    protected function validatePositiveIntInterval(
        array|string|null $interval,
        string $message,
    ): int {
        if ($interval === null || $interval === '') {
            throw new InvalidArgumentException($message);
        }

        $value = (int) $interval;

        if ($value < 1) {
            throw new InvalidArgumentException($message);
        }

        return $value;
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