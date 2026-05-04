<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SuperUser extends Model
{
    protected $table        =   'pharmacy_users_master';
    protected $primaryKey   =   'u_id';
    public $timestamps      =   false;

    protected $guarded = [];

    public function role()
    {
        return $this->hasOne(Role::class, "role_id", "u_role_id");
    }
}
