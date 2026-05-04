<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Answersheetmasking extends Model
{
    use HasFactory;

    protected $table = 'pharmacy_answersheet_masking_setup';
    protected $primaryKey   =   'pams_id';
    public $timestamps = false;
    protected $guarded = [];

    public function subjectDetails()
    {
        return $this->hasOne(Subject::class, 'general_code', 'pams_subject_code');
    }
    
}
