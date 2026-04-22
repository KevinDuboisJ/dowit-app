<?php

namespace App\Models;

use App\Traits\HasCreator;
use App\Traits\HasAccessScope;
use App\Traits\HasTeams;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Chain extends Model
{
    use SoftDeletes, HasTeams, HasCreator, HasAccessScope;

    protected function casts(): array
    {
        return [
            'trigger_conditions' => 'array',
            'actions'            => 'array',
            'ip_whitelist'       => 'array',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'identifier';
    }

    public function taskType()
    {
        return $this->belongsTo(TaskType::class);
    }

    public function triggerTaskType()
    {
        return $this->belongsTo(TaskType::class, 'trigger_task_type_id');
    }
}
