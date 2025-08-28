<?php

namespace App\Models\PATIENTLIST;

use App\Traits\HasPatientListTable;
use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    use HasPatientListTable;

    public function visits()
    {
        return $this->hasMany(Visit::class);
    }

    // public function latestVisit()
    // {
    //     return $this->hasOne(Visit::class)->latestOfMany();
    // }

    // public function scopeByIsAdmitted($query)
    // {
    //     return $query->whereHas('latestVisit', fn($q) => $q->whereNull('discharged_at'));
    // }

    // public function scopeByVisitNumber($query, string $number)
    // {
    //     return $query
    //         ->whereHas('latestVisit', fn($q) => $q->where('number', $number))
    //         ->with([
    //             'latestVisit' => fn($q) => $q
    //                 ->where('number', $number)
    //                 ->with(['bed', 'bed.room'])
    //                 ->take(1)
    //         ]);
    // }

    // public static function getByAdmittedPatientVisitId(string $search): ?Patient
    // {
    //     return self::query()
    //         ->byVisitNumber($search)
    //         ->byIsAdmitted()
    //         ->first();
    // }

    // public static function getByAdmittedPatientName(string $search): Collection
    // {
    //     return self::query()
    //         ->with(['latestVisit.bed.room'])
    //         ->byIsAdmitted()
    //         ->where(function ($query) use ($search) {
    //             $query->where('firstname', 'like', "%{$search}%")
    //                 ->orWhere('lastname', 'like', "%{$search}%")
    //                 ->orWhereRaw("CONCAT(firstname, ' ', lastname) LIKE ?", ["%{$search}%"]);
    //         })
    //         ->limit(40)
    //         ->get();
    // }
}
