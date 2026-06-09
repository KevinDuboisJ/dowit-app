<?php

namespace App\Models;

use App\Enums\EventEnum;
use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    static function resolveFromHostname(): Device
    {
        $identifier = self::getHostname();
        $device = self::where('identifier', $identifier)->first();
        $device = $device ?: new device(['identifier' => $identifier, 'is_registered' => false]);
        return $device;
    }

    public function setLastUsed(User $user): Device
    {
        $this->last_used_by = $user->id;
        $this->last_used_at = now();
        return $this;
    }

    static function getHostname(): ?string
    {
        return preg_replace('/\.monica\.be$/', '', gethostbyaddr($_SERVER['REMOTE_ADDR']));
    }

    public function logUserUsage(User $user, EventEnum $event): void
    {
        DeviceUserLog::create([
            'identifier' => $this->identifier,
            'user_id' => $user->id,
            'device_id' => $this->id,
            'event' => $event->value,
        ]);
    }

    public function LastUsedBy()
    {
        return $this->belongsTo(User::class, 'last_used_by');
    }
}