<?php

namespace App\Models\PATIENTLIST;

use Illuminate\Database\Eloquent\Model;


class Room extends Model
{
    protected $connection = 'patientlist';
    protected $fillable = ['number'];

    public function patients()
    {
        return $this->hasMany(PatientRoom::class);
    }

    public function beds()
    {
        return $this->hasMany(Bed::class);
    }
}
