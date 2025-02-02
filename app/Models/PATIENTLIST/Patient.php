<?php

namespace App\Models\PATIENTLIST;

use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    protected $connection = 'patientlist';

    public function currentRoom()
    {
        return $this->belongsTo(Room::class, 'current_room_id');
    }

    public function roomHistory()
    {
        return $this->hasMany(PatientRoom::class);
    }
}
