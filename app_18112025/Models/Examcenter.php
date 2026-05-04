<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Examcenter extends Model
{
    protected $table        =   'pharmacy_exam_center';
    protected $primaryKey   =   'id';
    public $timestamps      =   false;

    protected $guarded = [];
    
}