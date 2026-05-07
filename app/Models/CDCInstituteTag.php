<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use DB;
use Session;

class CDCInstituteTag extends Model
{
    protected $table        =   'pharmacy_cdc_ins_tagging';
    protected $primaryKey   =   'id';
    public $timestamps      =   false;

    protected $guarded = [];
}
