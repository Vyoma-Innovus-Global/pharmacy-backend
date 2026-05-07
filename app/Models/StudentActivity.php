<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentActivity extends Model
{
    use HasFactory;
    protected $table        =   'pharmacy_student_activities';
    protected $primaryKey   =   'a_id';
    public $timestamps      =   false;

    protected $guarded = [];
}
