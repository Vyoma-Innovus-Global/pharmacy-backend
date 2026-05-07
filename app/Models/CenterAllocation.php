<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CenterAllocation extends Model
{
    protected $table        =   'pharmacy_away_center_master';
    protected $primaryKey   =   'acm_id';
    public $timestamps      =   false;
 
    protected $guarded = [];
    
}
