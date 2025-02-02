<?php

namespace App\Models\EDB;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\EDB\Contract;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Person extends Model
{
    use HasFactory;

    protected $connection = 'edb';
    protected $table = 'person';
    protected $primaryKey = 'pe_id';

    public function scopeByDowitAccount($query)
    {
        return $query->whereHas('accounts', function ($accountQuery) {
            $accountQuery->where('an_at_id', 17);
        });
    }

    public function scopeByAdAccount($query)
    {
        return $query->whereHas('contracts', function ($contractQuery) {
            $contractQuery->where('cn_inactive', 0)
                ->whereHas('accounts', function ($accountQuery) {
                    $accountQuery->where('an_at_id', 2);
                });
        });
    }

    public function contracts()
    {
        return $this->hasMany(Contract::class, 'cn_pe_id');
    }

    public function accounts(): HasManyThrough
    {
        return $this->hasManyThrough(Account::class, Contract::class, 'cn_pe_id', 'an_cn_id', 'pe_id');
    }
}
