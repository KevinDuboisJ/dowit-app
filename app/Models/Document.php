<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    public function taskTypes()
    {
        return $this->belongsToMany(TaskType::class); // Laravel assumes `document_task_type`
    }
}
