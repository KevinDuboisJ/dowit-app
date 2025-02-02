<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\SoftDeletes;
use App\Scopes\CustomSoftDeletingScope;

trait HasCustomSoftDeletes
{
    use SoftDeletes;

    protected static function bootSoftDeletes()
    {
        static::addGlobalScope(new CustomSoftDeletingScope);
    }

    public function initializeSoftDeletes()
    {
        if (!isset($this->casts[$this->getDeletedAtColumn()])) {
            $this->casts[$this->getDeletedAtColumn()] = 'int';
        }
    }

    protected function runSoftDelete()
    {
        $query = $this->newModelQuery()->where($this->getKeyName(), $this->getKey());

        $this->{$this->getDeletedAtColumn()} = -2147483648;

        $query->update([$this->getDeletedAtColumn() => $this->{$this->getDeletedAtColumn()}]);
    }

    public function trashed()
    {
        return $this->{$this->getDeletedAtColumn()} === -2147483648;
    }

    public function restore()
    {
        $this->{$this->getDeletedAtColumn()} = 0;
        $this->save();
    }
}
