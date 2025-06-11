<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

// Per-team cache is temporarily disabled since Laravel no longer supports cache tags,
// and a clean alternative is not yet in place and test would be required for a custom one. Current performance is unaffected,
// so this workaround is acceptable for the time being.

class SettingService
{
    /**
     * @param  string  $code
     * @param  int|null  $teamId
     * @param  mixed  $default
     * @return mixed
     */
    public function get(string $code, ?int $teamId = null, mixed $default = null): mixed
    {
        // Cache per‐team, then fallback to global
        //$cacheKey = $this->cacheKey($code, $teamId);

        // return Cache::remember($cacheKey, now()->addHour(), function () use ($code, $teamId, $default) {
        if ($teamId) {
            $teamSetting = Setting::where('code', $code)
                ->where('scope', 'team')
                ->where('team_id', $teamId)
                ->first();
            if ($teamSetting) {
                return $teamSetting->value;
            }
        }

        $global = Setting::where('code', $code)
            ->where('scope', 'global')
            ->whereNull('team_id')
            ->first();

        return $global
            ? $global->value
            : $default;
        // });
    }

    /**
     * @param  string  $code
     * @param  mixed  $value
     * @param  string  $scope
     * @param  int|null  $teamId
     */
    public function set(string $code, mixed $value, string $scope = 'global', ?int $teamId = null): void
    {
        Setting::updateOrCreate(
            ['code' => $code, 'scope' => $scope, 'team_id' => $teamId],
            ['value' => $value, 'name' => $this->definition()[$code]['label']]
        );

        if ($scope === 'global') {
        }

        // Flush cache
        //Cache::forget($this->cacheKey($code, $teamId));
    }

    protected function cacheKey(string $code, ?int $teamId): string
    {
        return implode(':', ['settings', $code, $teamId ?? 'global']);
    }

    /**
     * Returns the “definition array” from the Definition class.
     */
    protected function definition(): array
    {
        return config('settings.definitions');
    }
}
