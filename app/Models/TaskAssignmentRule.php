<?php

namespace App\Models;

use App\Contracts\HasRequestingTeamsScopeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use App\Models\TaskType;
use App\Models\Space;
use App\Traits\HasCreator;
use App\Traits\HasAccessScope;
use App\Traits\HasTeams;

class TaskAssignmentRule extends Model implements HasRequestingTeamsScopeInterface
{
    use SoftDeletes, HasCreator, HasTeams, HasAccessScope;

    protected $casts = [
        'campuses' => 'array',
        'task_types' => 'array',
        'tags' => 'array',
        'spaces' => 'array',
        'spaces_to' => 'array',
    ];

    public function campus()
    {
        return $this->belongsTo(Campus::class, 'campuses');
    }

    public function taskType()
    {
        return $this->belongsTo(TaskType::class, 'task_types');
    }

    public function space()
    {
        return $this->belongsTo(Space::class, 'spaces');
    }

    public function spaceTo()
    {
        return $this->belongsTo(Space::class, 'spaces_to');
    }

    public function tags()
    {
        return $this->belongsTo(Tag::class, 'tags');
    }

    public function scopeByTaskMatch(Builder $query, Task $task): Builder
    {
        $rules = [
            'task_types' => $task->task_type_id,
            'spaces'     => $task->space_id,
            'spaces_to'  => $task->space_to_id,
            'campuses'   => $task->campus_id,
        ];

        // MySQL 5.7’s JSON path syntax doesn’t support the * wildcard in paths – that’s why we use the following helper
        // Helper: turns a PHP value into the SQL for JSON_CONTAINS(target, JSON_OBJECT('id', ?), '$')
        $containsId = function (Builder $q, $column, $id) {
            // only add the "contains" part if $id !== null
            if ($id !== null) {
                $q->orWhereRaw(
                    "JSON_CONTAINS(`{$column}`, JSON_OBJECT('id', ?), '$')",
                    [(int) $id]
                );
            }
        };

        foreach ($rules as $col => $value) {
            $query->where(function (Builder $q) use ($col, $value, $containsId) {
                $q->whereNull($col)
                    ->orWhereRaw("JSON_LENGTH({$col}) = 0");
                $containsId($q, $col, $value);
            });
        }

        // Tags (task may have multiple tags; require at least one overlap)
        $taskTagIds = $task->tags->pluck('id')->filter()->all();
        $query->where(function ($q) use ($taskTagIds, $containsId) {
            $q->whereNull('tags')
                ->orWhereRaw('JSON_LENGTH(tags) = 0');
            foreach ($taskTagIds as $tagId) {
                $containsId($q, 'tags', $tagId);
            }
        });

        return $query;
    }

    public function scopeByRequestingTeams(Builder $query, User $user): Builder
    {
        $teamIds = $user->getTeamIds();

        if (empty($teamIds)) {
            // user has no teams → nothing matches
            return $query->whereRaw('0 = 1');
        }

        // 1) Get task type IDs that have requestingTeams in user's teams
        $taskTypeIds = TaskType::whereHas('requestingTeams', function (Builder $q) use ($teamIds) {
            $q->whereKey($teamIds);
        })->pluck('id');

        if ($taskTypeIds->isEmpty()) {
            return $query->whereRaw('0 = 1');
        }

        // 2) Filter rules where JSON column task_types contains any of those ids
        return $query->where(function (Builder $q) use ($taskTypeIds) {
            foreach ($taskTypeIds as $taskTypeId) {
                $q->orWhereJsonContains('task_types', ['id' => $taskTypeId]);
            }
        });
    }
}
