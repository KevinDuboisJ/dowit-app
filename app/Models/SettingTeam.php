<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class SettingTeam extends Pivot
{
    protected $casts = [
        'value' => 'array', // Cast the value column as an array
    ];
}
