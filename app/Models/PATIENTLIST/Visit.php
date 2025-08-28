<?php

namespace App\Models\PATIENTLIST;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use App\Models\PATIENTLIST\Patient;
use App\Models\PATIENTLIST\Department;
use App\Models\PATIENTLIST\Room;
use App\Models\PATIENTLIST\Bed;
use App\Models\Campus;
use App\Models\Space;
use App\Traits\HasPatientListTable;

class Visit extends Model
{
    use HasPatientListTable;

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function campus()
    {
        return $this->belongsTo(Campus::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function bed()
    {
        return $this->belongsTo(Bed::class);
    }

    public function space()
    {
        return $this->belongsTo(Space::class);
    }

    public function bedVisits()
    {
        return $this->hasMany(BedVisit::class);
    }

    public function latestBedVisit()
    {
        return $this->hasMany(BedVisit::class)
            ->latest()  // Orders by 'created_at' by default
            ->first(); // Returns the latest entry
    }

    public function scopeByVisitNumber($query, string $number)
    {
        return $query->where('number', $number);
    }

    public function scopeByIsAdmitted($query)
    {
        return $query->whereNull('discharged_at');
    }

    public function scopeByPatientName($query, string $search)
    {
        return $query->whereHas('patient', function ($query) use ($search) {
            $query->where('firstname', 'like', "%{$search}%")
                ->orWhere('lastname', 'like', "%{$search}%")
                ->orWhereRaw("CONCAT(firstname, ' ', lastname) LIKE ?", ["%{$search}%"]);
        });
    }

    public static function getPatient(string $search): ?Visit
    {
        return self::query()
            ->byVisitNumber($search)
            ->byIsAdmitted()
            ->first();
    }

    public static function getByAdmittedPatientName(string $search): Collection
    {
        return self::query()
            ->with(['latestVisit.bed.room'])
            ->byIsAdmitted()
            ->where(function ($query) use ($search) {
                $query->where('firstname', 'like', "%{$search}%")
                    ->orWhere('lastname', 'like', "%{$search}%")
                    ->orWhereRaw("CONCAT(firstname, ' ', lastname) LIKE ?", ["%{$search}%"]);
            })
            ->limit(40)
            ->get();
    }
}
