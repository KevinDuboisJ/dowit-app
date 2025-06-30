<?php

namespace App\Models;

use App\Traits\HasCreator;
use App\Traits\HasTeams;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Chain extends Model
{
    use SoftDeletes, HasTeams, HasCreator;

    protected function casts(): array
    {
        return [
            'trigger_conditions' => 'array',
            'actions'            => 'array',
            'ip_whitelist'       => 'array',
            // 'is_active'          => 'boolean',
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
}
