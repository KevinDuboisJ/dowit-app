<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Asset;
use App\Traits\HasTeams;
use App\Traits\HasCreator;
use App\Traits\HasTeamOrUserScope;
class TaskType extends Model
{
    use HasFactory, HasCreator, HasTeams, HasTeamOrUserScope;

    protected $fillable = ['name', 'team_id'];

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function assets()
    {
        return $this->belongsToMany(Asset::class);
    }
}
