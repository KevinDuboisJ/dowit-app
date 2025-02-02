<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    public function roles()
    {
        return $this->BelongsToMany(Role::class, 'permission_role');
    }
}
