<?php

namespace App\Models\PATIENTLIST;

use App\Models\Campus;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $connection = 'patientlist';
    protected $fillable = ['number'];

    public function patients()
    {
        return $this->hasMany(PatientRoom::class);
    }

    public function campus()
    {
        return $this->belongsTo(Campus::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function beds()
    {
        return $this->hasMany(Bed::class);
    }
}
