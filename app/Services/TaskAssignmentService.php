<?php


namespace App\Services;

use App\Enums\TaskAssignmentRuleType;
use App\Models\Team;
use App\Models\Task;
use Illuminate\Database\Eloquent\Builder;

class TaskAssignmentService
{
    public static function getAssignmentRuleTeamsByTaskMatchAndTeams(Task $task)
    {
        return self::getExecutionTeamsByTaskMatch($task);
    }

    public static function getExecutionTeamsByTaskMatch(Task $task)
    {
        return self::getTeamsByTaskMatchAndType($task, TaskAssignmentRuleType::Execution);
    }

    public static function getVisibilityTeamsByTaskMatch(Task $task)
    {
        return self::getTeamsByTaskMatchAndType($task, TaskAssignmentRuleType::Visibility);
    }

    public static function getTeamsByTaskMatchAndType(Task $task, TaskAssignmentRuleType $type)
    {
        return Team::whereHas('taskAssignmentRules', function (Builder $query) use ($task, $type) {
            $query
                ->byType($type)
                ->byTaskMatch($task);
        })
            ->select('id', 'name')
            ->distinct();
    }
}
