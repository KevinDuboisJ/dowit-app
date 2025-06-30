<?php

namespace App\Models\PATIENTLIST;

use Illuminate\Database\Eloquent\Model;


class Department extends Model
{
    protected $connection = 'patientlist';
    protected $fillable = ['number'];

}
