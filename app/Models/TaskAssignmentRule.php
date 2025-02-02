<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\TaskType;
use App\Models\Space;

class TaskAssignmentRule extends Model
{
    use HasFactory;

    protected $casts = [
        'campuses' => 'array',
        'task_types' => 'array',
        'spaces' => 'array',
        'spaces_to' => 'array',
    ];

    public function teams()
    {
        return $this->belongsToMany(Team::class, 'task_assignment_rule_team');
    }

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
}
