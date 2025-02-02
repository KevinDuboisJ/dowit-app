<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasJson;
use DateTimeInterface;

class Logger extends Model
{
    use HasFactory;
    use HasJson;

    protected $table = 'logs';
    
    protected $casts = [
        'data' => 'array',
        'created_at' => 'custom_datetime:Y-m-d H-i-s',
        'updated_at' => 'custom_datetime:Y-m-d H-i-s',
    ];

    protected $guarded = [];


    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->timezone('Europe/Paris')->format('Y-m-d H:i:s');
    }

    public static function exception($traceableId, $traceableType, $data)
    {
        // Create a new log entry
        self::forceCreate([
            'traceable_id'   => $traceableId,  // If you're using polymorphic relations, this would be the related model's ID.
            'traceable_type' => $traceableType,  // If you're using polymorphic relations, this would be the related model's class name.
            'user_id'       => config('app.system_user_id'),
            'event'          => 'exception',
            'data'           => $data,
            'comment'        => 'Exception occurred',
        ]);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function traceable()
    {
        return $this->morphTo();
    }

    public function task()
    {
        return $this->belongsTo(Task::class, 'traceable_id', 'id');
    }
}
