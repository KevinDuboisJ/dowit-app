<?php

namespace App\Models;

use App\Traits\HasCreator;
use App\Traits\HasTeamOrUserScope;
use App\Traits\HasTeams;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Builder;
class Tag extends Model
{
    use HasCreator, HasTeams, HasTeamOrUserScope;

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

    public function scopeByUserInput(Builder $query, ?string $userInput): Builder
    {

        return $query->when($userInput, function ($query, $userInput) {
            $userInput = trim(strip_tags($userInput));
            $searchWords = array_filter(explode(' ', $userInput)); // Remove empty words

            $query->where(function ($query) use ($searchWords) {
                foreach ($searchWords as $word) {
                    $query->where('tags.name', 'LIKE', "%{$word}%");
                }
            });
        });
    }
}
