<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Database\Eloquent\SoftDeletingScope;


class SpaceSoftDeletingScope extends SoftDeletingScope implements Scope
{

    public function apply(Builder $builder, Model $model){
        $builder->where($model->getQualifiedDeletedAtColumn(), '1970-01-02 00:00:00');
    }

}