<?php

namespace App\Models;

use App\Enums\EventEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Device extends Model
{
    protected $fillable = [
        'identifier',
        'description',
        'type',
        'campus_id',
        'is_registered',
        'last_used_by',
        'last_used_at',
    ];

    protected $casts = [
        'is_registered' => 'boolean',
        'last_used_at' => 'datetime',
    ];

    public static function resolveFromHostname(): Device
    {
        $identifier = self::getHostname();
        $device = self::where('identifier', $identifier)->first();

        return $device ?: new self(['identifier' => $identifier, 'is_registered' => false]);
    }

    public function scopeRegistered(Builder $query): Builder
    {
        return $query->where('is_registered', true);
    }

    public function setLastUsed(User $user): Device
    {
        $this->last_used_by = $user->id;
        $this->last_used_at = now();

        return $this;
    }

    public static function getHostname(): ?string
    {
        return preg_replace('/\.monica\.be$/', '', gethostbyaddr($_SERVER['REMOTE_ADDR']));
    }

    public function logUserUsage(User $user, EventEnum $event): void
    {
        DeviceUserLog::create([
            'identifier' => $this->identifier,
            'user_id' => $user->id,
            'device_id' => $this->id,
            'event' => $event,
        ]);
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function lastUsedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_used_by');
    }

    public function userLogs(): HasMany
    {
        return $this->hasMany(DeviceUserLog::class);
    }
}
