<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Team;

class Setting extends Model
{
    use HasFactory;

    protected $casts = [
        'value' => 'array', // Cast the value column as an array
    ];

    public function team(): BelongsTo
    {
        return $this->BelongsTo(Team::class);
    }

    // Scope for global settings
    public function scopeGlobal($query)
    {
        return $query->where('type', 'global');
    }

    // Scope for team settings
    public function scopeForTeam($query, $teamId)
    {
        return $query->where('type', 'team')->where('team_id', $teamId);
    }
}
