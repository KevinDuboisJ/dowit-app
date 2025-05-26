<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaskChain extends Model
{
    use SoftDeletes;

    public function taskType()
    {
        return $this->belongsTo(TaskType::class);
    }
}
