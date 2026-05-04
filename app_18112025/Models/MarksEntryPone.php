<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarksEntryPone extends Model
{
    use HasFactory;

    protected $table        =   'pharmacy_exam_marks_pone';
    protected $primaryKey   =   'id';

    protected $guarded = [];
}
