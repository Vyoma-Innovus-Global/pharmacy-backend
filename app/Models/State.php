<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class State extends Model
{
    protected $table        =   'pharmacy_state_master';
    protected $primaryKey   =   'state_id_pk';
    public $timestamps      =   false;

    protected $guarded = [];
}
