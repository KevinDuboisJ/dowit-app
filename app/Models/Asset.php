<?php

namespace App\Models;

use App\Traits\HasTeams;
use App\Traits\HasCreator;
use App\Traits\HasTeamOrUserScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asset extends Model
{
    use SoftDeletes, HasCreator, HasTeams, HasTeamOrUserScope;

    public function taskTypes()
    {
        return $this->belongsToMany(TaskType::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }

}
