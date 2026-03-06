<?php

namespace App\Models\PATIENTLIST;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Bed extends Model
{
    protected $connection = 'patientlist';
    protected $fillable = ['number'];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function bedVisits()
    {
        return $this->hasMany(BedVisit::class);
    }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when(($filters['department'] ?? 'all') !== 'all', function (Builder $query) use ($filters) {
                $query->whereHas('room.department', function (Builder $query) use ($filters) {
                    $query->where('number', $filters['department']);
                });
            })
            ->when(($filters['campus'] ?? 'all') !== 'all', function (Builder $query) use ($filters) {
                $query->whereHas('room.campus', function (Builder $query) use ($filters) {
                    $query->where('name', $filters['campus']);
                });
            })
            ->when(($filters['room'] ?? 'all') !== 'all', function (Builder $query) use ($filters) {
                $query->whereHas('room', function (Builder $query) use ($filters) {
                    $query->where('number', $filters['room']);
                });
            })
            ->when(($filters['bed'] ?? 'all') !== 'all', function (Builder $query) use ($filters) {
                $query->where('number', $filters['bed']);
            })
            ->when(($filters['needs_cleaning_only'] ?? false), function (Builder $query) {
                $query->whereNull('cleaned_at');
            })
            ->when(($filters['show_occupied_only'] ?? false), function (Builder $query) {
                $query->whereNotNull('occupied_at');
            })
            ->when(($filters['show_cleaned_only'] ?? false), function (Builder $query) {
                $query->whereNotNull('cleaned_at');
            })
            ->when(($filters['room_type'] ?? 'all') === 'one', function (Builder $query) {
                $query->whereHas('room', function (Builder $query) {
                    $query->withCount('beds')->having('beds_count', 1);
                });
            })
            ->when(($filters['room_type'] ?? 'all') === 'two', function (Builder $query) {
                $query->whereHas('room', function (Builder $query) {
                    $query->withCount('beds')->having('beds_count', 2);
                });
            });
    }
}
