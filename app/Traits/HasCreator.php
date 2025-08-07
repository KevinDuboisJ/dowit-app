<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

trait HasCreator
{
    /**
     * Boot the AssignsUserId trait for a model.
     *
     * @return void
     */
    public static function bootHasCreator()
    {
        static::creating(function ($model) {
            if (Auth::check()) {
                $model->created_by = Auth::id() ?? config('app.system_user_id');
            }
        });
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeByCreator(Builder $query, User $user): Builder
    {
        return $query->where('created_by', $user->id);
    }

    public function isCreator(?User $user = null): bool
    {
        if ($user === null) {
            $user = Auth::user();
        }

        return $this->created_by === $user->id;
    }
}
