<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Studentinfo extends Model
{
    use HasFactory;
    protected $table        =   'pharmacy_student_extraInfo';
    protected $primaryKey   =   'ei_id';
    public $timestamps      =   true;

    protected $guarded = [];
}
