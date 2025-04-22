<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskChain extends Model
{
    use HasFactory;

    public function taskType()
    {
        return $this->belongsTo(TaskType::class);
    }
}
