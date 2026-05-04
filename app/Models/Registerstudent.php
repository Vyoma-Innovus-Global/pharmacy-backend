<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Registerstudent extends Model
{
    use HasFactory;
    protected $table        =   'pharmacy_register_student_final';
    protected $primaryKey   =   's_id';
    public $timestamps      =   true;

    protected $guarded = [];
    // new added
    public function institute()
    {
        return $this->belongsTo(Institute::class, 's_inst_code', 'i_code');
    }

    public function enrollment()
    {
        return $this->hasOne(Enrollment::class, 'enrl_form_num', 's_appl_form_num');
    }
    //
    public function getElective()
    {
        return $this->hasOne(Elective::class, 'elect_inst_code', 's_inst_code');
    }

    public function getExtraInfo()
    {
        return $this->hasOne(Studentinfo::class, 'ei_appl_form_num', 's_appl_form_num');
    }
    
}
