<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Notification extends Model
{
  protected $fillable = ['read_at', 'notifiable_id'];
  protected $casts = [
    'id' => 'string',
    'data' => 'array'
  ];

  public function getModalColumns(): array
  {
    return [
      'name' => [
        'label' => 'Naam',
        'type' => self::getDefaultTypeForColumn('name'),
        'searchable' => true,
      ],
    ];
  }

  public function getTypeAttribute($value)
  {
    if ($value === 'App\Notifications\UltimoChangeNotification') {
      return 'Ultimo';
    }
  }

  public function user()
  {
    return $this->belongsTo(User::class, 'notifiable_id', 'id');
  }
}
