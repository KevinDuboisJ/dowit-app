<?php

namespace App\Models\PATIENTLIST;

use Illuminate\Database\Eloquent\Model;

class PatientRoom extends Model
{
    protected $connection = 'patientlist';
    protected $table = 'patient_room';
    protected $fillable = ['pat_id', 'room_id'];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }
}
