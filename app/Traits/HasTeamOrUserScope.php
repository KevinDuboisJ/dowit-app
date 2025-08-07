<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

trait HasTeamOrUserScope
{
    public static function bootHasTeamOrUserScope(): void
    {
        static::addGlobalScope('team_access', function (Builder $query) {

            if (static::class === User::class) {
                return;
            }

            $user = Auth::user();

            if (! $user || $user->isSuperAdmin()) {
                return;
            }

            /** @var self $model */
            $model = new static;

            $query->where(function ($query) use ($model, $user) {
                // logger($user->teams->pluck('id')->toArray());
                $query->where(function ($q) use ($user, $model) {
                    $model->scopeByBelongsToTeamIds($q, $user->teams->pluck('id')->toArray());

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
                });
            });
        });
    }
}
