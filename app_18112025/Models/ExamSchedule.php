<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamSchedule extends Model
{
    protected $table        =   'pharmacy_exam_schedule';
    protected $primaryKey   =   'exam_id';
    public $timestamps      =   false;
 
    protected $guarded = [];
   
}
