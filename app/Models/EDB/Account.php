<?php

namespace App\Models\EDB;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\EDB\Contract;
use Illuminate\Database\Eloquent\Builder;

class Account extends Model
{
    use HasFactory;

    protected $connection = 'edb';
    protected $table = 'account';
    protected $primaryKey = 'an_id';

    public function scopeByAdUsername(Builder $query, $username) {
        return $query->where('an_userid_1', $username)->where('an_at_id', 2)->where('an_inactive', 0);
    }

    public function scopeByDowitAccount(Builder $query, $active = true) {
        return $query->where('an_at_id', config('app.edb.dowit_account_type_id'))->where('an_inactive', $active ? 0 : 1);
    }

    public function scopeByDowitUsername(Builder $query, $username) {
        return $query->where('an_userid_1', $username)->where('an_at_id', config('app.edb.dowit_account_type_id'))->where('an_inactive', 0);
    }
    
    public function contract()
    {
        return $this->belongsTo(Contract::class, 'an_cn_id');
    }

}
