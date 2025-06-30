<?php

namespace App\Models\PATIENTLIST;

use Illuminate\Database\Eloquent\Model;
use App\Models\PATIENTLIST\Patient;
use App\Models\PATIENTLIST\Department;
use App\Models\PATIENTLIST\Room;
use App\Models\PATIENTLIST\Bed;
use App\Models\Campus;
use App\Models\Space;


class Visit extends Model
{
    protected $connection = 'patientlist';
    protected $fillable = ['number', 'admission', 'discharge'];

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

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function bed()
    {
        return $this->belongsTo(Bed::class);
    }

    public function space()
    {
        return $this->belongsTo(Space::class);
    }
}
