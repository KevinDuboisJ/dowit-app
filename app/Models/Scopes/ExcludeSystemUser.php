<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class ExcludeSystemUser implements Scope
{

    protected int $systemUserId;

    public function __construct()
    {
        $this->systemUserId = config('app.system_user_id');
    }

    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model)
    {
        $builder->where('users.id', '!=', $this->systemUserId);
    }
}
