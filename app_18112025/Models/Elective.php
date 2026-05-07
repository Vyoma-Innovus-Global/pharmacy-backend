<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Elective extends Model
{
    protected $table        =   'pharmacy_inst_elective';
    protected $primaryKey   =   'elect_id';
    public $timestamps      =   false;

    protected $guarded = [];

}
