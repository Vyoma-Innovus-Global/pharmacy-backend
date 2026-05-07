<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use DB;
use Session;

class CDC extends Model
{
    protected $table        =   'pharmacy_CDC_master';
    protected $primaryKey   =   'id';
    public $timestamps      =   false;

    protected $guarded = [];
}
