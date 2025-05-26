<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Tag extends Model
{
    public function taskPlanners()
    {
        return $this->belongsToMany(TaskPlanner::class);
    }

    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn(string $value) => ucwords($value), // Display: "High Priority"
            set: fn(string $value) => strtolower($value), // Store: "high priority"
        );
    }
}
