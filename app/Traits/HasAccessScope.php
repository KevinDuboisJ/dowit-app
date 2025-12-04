<?php

namespace App\Traits;

use App\Contracts\HasRequestingTeamsScopeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

trait HasAccessScope
{
    public static function bootHasAccessScope(): void
    {
        static::addGlobalScope('access_scope', function (Builder $query) {

            if (static::class === User::class) {
                return;
            }

            $user = Auth::user()?->loadMissing('teams:id,name');

            if (! $user || $user->isSuperAdmin()) {
                return;
            }

            /** @var self $model */
            $model = new static;

            $query->where(function ($query) use ($model, $user) {
                $query->where(function ($q) use ($user, $model) {

                    $teamIds = $user->getTeamIds();

                    if (count($teamIds) === 0) {
                        return $q->whereRaw('0 = 1');
                    }

                    $relation = method_exists($model, 'teamRelationPath')
                        ? $model->teamRelationPath()
                        : 'teams';

                    $q->whereHas($relation, function ($q) use ($teamIds) {
                        $q->whereIn('teams.id', $teamIds);
                    });


                    if (method_exists($model, 'scopeByCreator')) {
                        $q->orWhere(function ($subQuery) use ($user, $model) {
                            $model->scopeByCreator($subQuery, $user);
                        });
                    }

                    if (method_exists($model, 'scopeByAssignees')) {
                        $q->orWhere(function ($subQuery) use ($user, $model) {
                            $model->scopeByAssignees($subQuery, $user);
                        });
                    }

                    if ($model instanceof HasRequestingTeamsScopeInterface) {
                        $q->orWhere(function ($subQuery) use ($user, $model) {
                            $model->scopeByRequestingTeams($subQuery, $user);
                        });
                    }
                });
            });
        });
    }
}
