<?php

namespace App\Traits;

use App\Contracts\HasVisibilityTeamsScopeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait HasAccessScope
{
    public static function bootHasAccessScope(): void
    {
        static::addGlobalScope('access_scope', function (Builder $query) {

            // Get the current authentication guard.
            $guard = Auth::guard();

            // Do not apply access restrictions when there is no authenticated user
            // or when the authenticated user is a super admin.
            if (! $guard->hasUser() || $guard->user()->isSuperAdmin()) {
                return;
            }

            // Get the authenticated user and make sure the user's teams are loaded.
            $user = $guard->user();
            $user->loadMissing('teams:id,name');

            // Create an instance of the current model so we can check
            // which access-related methods or interfaces it supports.
            /** @var self $model */
            $model = new static;

            // Group all access rules together so the generated SQL keeps
            // the correct OR/AND logic.
            $query->where(function ($query) use ($model, $user) {
                $query->where(function ($q) use ($user, $model) {

                    // Get the team IDs linked to the authenticated user.
                    $teamIds = $user->getTeamIds();

                    // If the user has no teams, deny access to all records.
                    if (count($teamIds) === 0) {
                        return $q->whereRaw('0 = 1');
                    }

                    // Determine which relationship should be used to check team access.
                    // By default, the model should have a "teams" relationship.
                    // A model can override this by defining teamRelationPath().
                    $relation = method_exists($model, 'teamRelationPath')
                        ? $model->teamRelationPath()
                        : 'teams';

                    // Allow access when the record is linked to one of the user's teams.
                    $q->whereHas($relation, function ($q) use ($teamIds) {
                        $q->whereIn('teams.id', $teamIds);
                    });

                    // Allow access when the model defines custom creator-based access
                    // and the current user matches that creator logic.
                    if (method_exists($model, 'scopeByCreator')) {
                        $q->orWhere(function ($subQuery) use ($user, $model) {
                            $model->scopeByCreator($subQuery, $user);
                        });
                    }

                    // Allow access when the model supports requesting-team access.
                    // This is useful for models where access is based on teams that requested something,
                    // instead of only the teams directly linked to the record.
                    if ($model instanceof HasVisibilityTeamsScopeInterface) {
                        $q->orWhere(function ($subQuery) use ($teamIds, $model) {
                            $model->scopeByVisibilityTeams($subQuery, $teamIds);
                        });
                    }
                });
            });
        });
    }
}