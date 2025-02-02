<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Collection;
use App\Models\Setting;
use App\Models\SettingTeam;

class Team extends Model
{
    use HasFactory;

    protected $casts = [
        'autoassign_rules' => 'array'
    ];

    public function scopeByUserTeams(Builder $query)
    {
        $user = Auth::user();

        if ($user) {
            // Get all team IDs the user belongs to
            $teamIds = $user->teams->pluck('id')->toArray();

            // Apply the filter based on the user's teams
            $query->whereIn('id', $teamIds);

            return $query;
        }

        throw new AuthenticationException('Gebruiker heeft geen teams');
    }

    public static function getAllNestedSubteams($team)
    {
        // Initialize an empty collection to store all subteams
        $allSubteams = new Collection();

        // Fetch the immediate subteams of the current team
        $subteams = $team->subteams()->get();

        // Add all immediate subteams to the collection
        $allSubteams = $allSubteams->merge($subteams);

        // Loop through each subteam to get its nested subteams recursively
        foreach ($subteams as $subteam) {
            // Recursively fetch the nested subteams of this subteam
            $nestedSubteams = self::getAllNestedSubteams($subteam);

            // Merge the nested subteams into the main collection
            $allSubteams = $allSubteams->merge($nestedSubteams);
        }

        // Return the collection of all nested subteams
        return $allSubteams;
    }


    /**
     * Recursive function to add a team and its subteams to the collection,
     * and assign a subteam-row class based on the nested level.
     * 
     * @param Team $team
     * @param EloquentCollection $sortedTeams
     */
    private function getSubteams(Team $team, Collection &$sortedTeams, int $level)
    {
        // Add a temporary attribute to the model to reflect the nesting level
        $team->classname = 'team-level-' . $level;

        // Add the current team to the collection
        $sortedTeams->push($team);

        // Fetch and sort its subteams
        $subteams = $team->subteams()->orderBy('name')->get();

        // Recursively add each subteam and their respective subteams, increasing the level
        foreach ($subteams as $subteam) {
            self::getSubteams($subteam, $sortedTeams, $level + 1);  // Increment the level for subteams
        }
    }

    public function getSettingsByUserDefaultTeam(): Collection
    {
        return Setting::selectRaw('settings.code, COALESCE(setting_team.value, settings.value) as value, settings.name')
            ->leftJoin('setting_team', function ($join) {
                $join->on('settings.id', '=', 'setting_team.setting_id')
                    ->where('setting_team.team_id', '=', $this->id); // `$this->id` is the current team ID
            })
            ->pluck('value', 'code');
    }

    public function setAutoassignRulesAttribute($value)
    {
        $this->attributes['autoassign_rules'] = empty($value) ? null : json_encode($value);
    }

    public function parentTeam()
    {
        return $this->belongsTo(Team::class, 'parent_team_id');
    }

    public function subteams()
    {
        return $this->hasMany(Team::class, 'parent_team_id');
    }


    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_user')->where('users.id', '!=', '1')->withTimestamps();
    }

    public function taskTypes()
    {
        return $this->hasMany(TaskType::class);
    }

    public function tasks()
    {
        return $this->belongsToMany(Task::class, 'task_team');
    }

    public function settings()
    {
        return $this->belongsToMany(Setting::class, 'setting_team')
            ->withTimestamps()
            ->withPivot('value')
            ->using(SettingTeam::class);
    }
}
