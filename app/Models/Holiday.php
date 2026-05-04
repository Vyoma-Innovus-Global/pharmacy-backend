<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    protected $table        =   'pharmacy_holiday_table';
    protected $primaryKey   =   'hol_id';
    public $timestamps      =   false;
 
    protected $guarded = [];
   
}
