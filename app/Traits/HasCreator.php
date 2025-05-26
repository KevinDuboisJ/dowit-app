<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;
use App\Models\User;

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

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
