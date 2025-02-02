<?php

namespace App\Models\EDB;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\EDB\Account;
use App\Models\EDB\Person;
use Illuminate\Database\Eloquent\Builder;

class Contract extends Model
{
    use HasFactory;

    protected $connection = 'edb';
    protected $table = 'contract';
    protected $primaryKey = 'cn_id';

    public function scopeByDowitAccount(Builder $query, $active = true)
    {
        return $query->whereHas('accounts', function ($accountQuery) use ($active) {
            $accountQuery->where('an_at_id', 17)->where('an_inactive', $active ? 0 : 1);
        });
    }

    public function scopeByActive(Builder $query, $inactive = false)
    {
        return $query->where('cn_inactive', $inactive);
    }

    public function accounts()
    {
        return $this->hasMany(Account::class, 'an_cn_id');
    }

    public function person()
    {
        return $this->belongsTo(Person::class, 'cn_pe_id');
    }
}
