<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Asset;

class TaskType extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'team_id'];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function assets()
    {
        return $this->belongsToMany(Asset::class);
    }
}
