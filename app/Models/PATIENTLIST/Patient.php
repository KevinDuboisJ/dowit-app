<?php

namespace App\Models\PATIENTLIST;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    protected $connection = 'patientlist';

    public function visits()
    {
        return $this->hasMany(Visit::class);
    }

    public function visit()
    {
        return $this->hasOne(Visit::class);
    }

    public static function getByPatientVisitId(string $search): ?Patient
    {
        return Patient::query()
            ->whereHas('visit', function ($query) use ($search) {
                $query->where('number', $search);
            })
            ->with(['visit' => function ($query) use ($search) {
                $query->where('number', $search)->with(['bed', 'bed.room'])->take(1);
            }])
            ->first();
    }

    public static function getByPatientName(string $search): Collection
    {
        return Patient::query()
            ->with(['visit.bed', 'visit.bed.room'])
            ->where(function ($query) use ($search) {
                $query->where('firstname', 'like', "%{$search}%")
                    ->orWhere('lastname', 'like', "%{$search}%")
                    ->orWhereRaw("CONCAT(firstname, ' ', lastname) LIKE ?", ["%{$search}%"])
                    ->latest('admission');
            })
            ->limit(40)
            ->get();
    }
}
