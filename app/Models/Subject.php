<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    protected $table        =   'pharmacy_subjects_master';
    protected $primaryKey   =   'id';
    public $timestamps      =   false;

    protected $guarded = [];

}
