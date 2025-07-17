<?php

namespace App\Models\PATIENTLIST;

use Illuminate\Database\Eloquent\Model;


class BedVisit extends Model
{
    protected $connection = 'patientlist';
    protected $table = 'bed_visits';

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function bed()
    {
        return $this->belongsTo(Bed::class);
    }
}
