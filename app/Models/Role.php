<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $guarded = [];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Parent role in the hierarchy (nullable root).
     */
    public function parent()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    /**
     * Direct child roles.
     */
    public function children()
    {
        return $this->hasMany(Role::class, 'role_id');
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class)->withTimestamps();
    }
}
