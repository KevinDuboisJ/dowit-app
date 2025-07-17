<?php

namespace App\Models\PATIENTLIST;

use Illuminate\Database\Eloquent\Model;


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
}
