<?php

namespace App\Services;

use App\Enums\EventEnum;
use App\Enums\TeamEnum;
use App\Models\Device;
use App\Models\DeviceUserLog;
use App\Models\User;

class DeviceSelectionService
{
    public const SESSION_KEY = 'selected_device_id';

    public function isRequiredFor(User $user): bool
    {
        return $user->teams()
            ->whereIn('teams.id', [TeamEnum::SchoonmaakCA->value, TeamEnum::SchoonmaakCD->value])
            ->exists();
    }

    public function hasSelectedDeviceInSession(): bool
    {
        return session()->has(self::SESSION_KEY);
    }

    public function restoreTodaySelection(User $user): ?Device
    {
        $log = DeviceUserLog::query()
            ->where('user_id', $user->id)
            ->where('event', EventEnum::UserSelectedDevice)
            ->whereDate('created_at', today())
            ->whereNotNull('device_id')
            ->latest()
            ->first();

        if (! $log?->device_id) {
            return null;
        }

        session()->put(self::SESSION_KEY, $log->device_id);

        return $log->device;
    }

    public function selectDevice(User $user, Device $device, EventEnum $event = EventEnum::UserSelectedDevice): void
    {
        $device->setLastUsed($user)->save();
        $device->logUserUsage($user, $event);

        session()->put(self::SESSION_KEY, $device->id);
    }
}
