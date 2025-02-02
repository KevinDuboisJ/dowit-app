<?php
namespace App\Traits;
use Illuminate\Support\Str;

trait HasUuid
{
    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $model->{$model->getKeyName()} = (string) Str::uuid();
        });
        
    }

    public function initializeHasUuid()
    {
        $this->incrementing = false;
        $this->keyType = 'string';
    }
    
}