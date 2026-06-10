<?php

namespace App\Models;

use App\Enums\EventEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceUserLog extends Model
{
    protected $fillable = [
        'identifier',
        'user_id',
        'device_id',
        'event',
    ];

    protected $casts = [
        'event' => EventEnum::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
