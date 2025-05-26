<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

trait SoftDeletesWithUser
{
    use SoftDeletes;
    
    protected static function booted()
    {
        static::deleting(function ($model) {
            if (Auth::check() && !$model->isForceDeleting()) {
                $model->deleted_by = Auth::id();
                $model->saveQuietly(); // avoid infinite loop
            }
        });
    }

    public function deleter()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
