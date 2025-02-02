<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;

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
                $model->created_by = Auth::id();
            }
        });
    }
}