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
      $userInput = trim(strip_tags($userInput));
      $searchWords = array_filter(explode(' ', $userInput)); // Remove empty words

      $query->where(function ($query) use ($searchWords) {
        foreach ($searchWords as $word) {
          $query->where('spaces.name', 'LIKE', "%{$word}%");
        }
      })
        ->orWhere('spaces._spccode', 'LIKE', "%{$userInput}%"); // Check full input in `_spccode`
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
