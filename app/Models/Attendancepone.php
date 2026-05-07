<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Attendancepone extends Model
{
    protected $table        =   'exam_attendance_pone';
    protected $primaryKey   =   'ea_id';
    public $timestamps      =   false;

    protected $guarded = [];

    public function student()
    {
        return $this->hasOne(Registerstudent::class, "s_appl_form_num", "ea_form_number");
    }

    
    
}