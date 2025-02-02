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
}
