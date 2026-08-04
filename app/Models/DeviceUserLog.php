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

    public static function log(Device $device, User $user, EventEnum $event): void
    {
        if (self::shouldSkipLog($device, $user, $event)) {
            return;
        }

        self::create([
            'identifier' => $device->identifier,
            'user_id' => $user->id,
            'device_id' => $device->id,
            'event' => $event,
        ]);
    }

    private static function shouldSkipLog(Device $device, User $user, EventEnum $event): bool
    {
        if ($event === EventEnum::UserSwitchedDevice) {

            $previousLog = self::query()
                ->where('user_id', $user->id)
                ->whereDate('created_at', today())
                ->where('event', EventEnum::UserSwitchedDevice->value)
                ->latest()
                ->first();

            return (int) $previousLog?->device_id === (int) $device->id;
        }

        return false;
    }
}
