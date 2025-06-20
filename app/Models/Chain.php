<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Chain extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'trigger_conditions' => 'array',
            'actions'            => 'array',
            'ip_whitelist'       => 'array',
            // 'is_active'          => 'boolean',
        ];
    }

    public function taskType()
    {
        return $this->belongsTo(TaskType::class);
    }
}
