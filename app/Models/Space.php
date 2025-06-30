<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasCustomSoftDeletes;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;

class Space extends Model
{
  use HasCustomSoftDeletes;

  const DELETED_AT = 'SpcRecStatus';
  protected $connection = 'spaces';

  protected function casts(): array
  {
    return [
      'SpcRecStatus' => 'int',
      'SpcRecCreateDate' => 'datetime',
    ];
  }

  public function scopeByUserInput(Builder $query, ?string $userInput): Builder
  {
    return $query->when($userInput, function ($query, $userInput) {
      // Normalize user input: remove +, -, _, and spaces
      $normalizedInput = strtolower(preg_replace('/[+\-_ ]+/', '', trim(strip_tags($userInput))));
      $words = array_filter(preg_split('/\s+/', $normalizedInput));

      $query->where(function ($query) use ($words) {
        foreach ($words as $word) {
          $query->whereRaw(
            "REPLACE(REPLACE(REPLACE(REPLACE(LOWER(spaces.name), '+', ''), '-', ''), '_', ''), ' ', '') LIKE ?",
            ["%{$word}%"]
          );
        }
      })
        ->orWhere('spaces._spccode', 'LIKE', "%{$userInput}%");
    });
  }

  protected function serializeDate(DateTimeInterface $date)
  {
    return $date->format('Y-m-d H:i:s');
  }

  public function campus()
  {
    return $this->BelongsTo(Campus::class);
  }

  public function tasks()
  {
    return $this->hasMany(Task::class, 'space_id');
  }

  public function logs()
  {
    return $this->morphMany(Logger::class, 'traceable');
  }
}
