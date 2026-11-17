<?php

namespace App\Services;

use App\Enums\EventEnum;
use App\Enums\CampusEnum;
use App\Enums\TeamEnum;
use App\Models\Device;
use App\Models\DeviceUserLog;
use App\Models\User;
use Illuminate\Http\Request;

class DeviceSelectionService
{
    public const SESSION_KEY = 'selected_device_id';
    public const SWITCHING_SESSION_KEY = 'is_switching_device';
    public const IMPERSONATION_SESSION_KEY = 'is_impersonated';

    public function isRequiredFor(User $user): bool
    {
        if (session()->get(self::IMPERSONATION_SESSION_KEY, false)) {
            return false;
        }

        return $user->belongsToTeam([
            TeamEnum::SchoonmaakCA->value,
            TeamEnum::SchoonmaakCD->value,
        ]) && $user->isRegularUser();
    }

    public function hasSelectedDeviceInSession(): bool
    {
        return session()->has(self::SESSION_KEY);
    }

    /**
     * Return the campuses where the user may select a cleaning cart.
     */
    public function getAllowedCampusIds(User $user): array
    {
        $campusIds = [];

        if ($user->belongsToTeam(TeamEnum::SchoonmaakCA->value)) {
            $campusIds[] = CampusEnum::CA->value;
        }

        if ($user->belongsToTeam(TeamEnum::SchoonmaakCD->value)) {
            $campusIds[] = CampusEnum::CD->value;
        }

        return array_values(array_unique($campusIds));
    }

    public function getSelectableDevicesQuery(User $user)
    {
        return Device::query()
            ->registered()
            ->where('type', 'poetskar')
            ->whereIn('campus_id', $this->getAllowedCampusIds($user));
    }

    public function getLastCleaningCartSelection(User $user): ?Device
    {
        $log =  DeviceUserLog::query()
            ->where('user_id', $user->id)
            ->whereDate('created_at', today())
            ->whereNotNull('device_id')
            ->whereHas('device', function ($query) {
                $query->where('type', 'poetskar');
            })
            ->latest()
            ->first();

        if (! $log?->device_id) {
            return null;
        }

        return $log->device;
    }

    public function selectDevice(User $user, Device $device, EventEnum $event = EventEnum::UserSelectedDevice): void
    {
        $device->setLastUsed($user)->save();
        DeviceUserLog::log($device, $user, $event);

        session()->put(self::SESSION_KEY, $device->id);
    }

    public static function clearSelection(): void
    {
        session()->forget([
            self::SESSION_KEY,
            self::SWITCHING_SESSION_KEY,
        ]);
    }
}
