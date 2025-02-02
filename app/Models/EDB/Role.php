<?php

namespace App\Models\EDB;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\EDB\Contract;
use Illuminate\Database\Eloquent\Builder;

class Role extends Model
{
    use HasFactory;

    protected $connection = 'edb';
    protected $table = 'role';
    protected $primaryKey = 'ro_id';

    public function contract()
    {
        return $this->belongsTo(Contract::class, 'an_cn_id');
    }

    public function scopeByAccount(Builder $query, int $accountId)
    {
        return $query->join('account_role', 'role.ro_id', '=', 'account_role.ar_ro_id')  // Join account_role table
            ->where('account_role.ar_an_id', $accountId)  // Filter by dowitAccountId
            ->where('account_role.ar_stopdate', '0000-00-00 00:00:00')  // Filter by stop date
            ->select('account_role.*', 'role.*');  // Select all columns from both tables
    }

    public function scopeByDowitAccountUsername(Builder $query, string $username)
    {
        $query->from('account')
            ->where('an_userid_1', $username)
            ->where('an_at_id', 18)
            ->where('an_inactive', 0)
            ->join('account_role', 'account.an_id', '=', 'account_role.ar_an_id')  // Join account_role on account ID
            ->join('role', 'role.ro_id', '=', 'account_role.ar_ro_id')  // Join role table
            ->where('account_role.ar_stopdate', '0000-00-00 00:00:00')  // Filter by stop date
            ->select('account_role.*', 'role.*', 'account.an_id');  // Select required columns
    }
}
