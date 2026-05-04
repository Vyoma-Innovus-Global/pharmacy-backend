<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    use HasFactory;
    protected $primaryKey = 'enrl_id';
    protected $table = 'pharmacy_enrollment';
    public $timestamps = true;
    protected $guarded = [];

    public function student()
    {
        return $this->belongsTo(Registerstudent::class, 'enrl_form_num', 's_appl_form_num');
    }
}
