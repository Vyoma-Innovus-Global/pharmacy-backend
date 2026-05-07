<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Examcenter;

class Rollnomodel extends Model
{
    protected $table        =   'pharmacy_roll_no';
    protected $primaryKey   =   'id';
    public $timestamps      =   false;

    protected $guarded = [];

    public function examcenter()
    {
        return $this->hasOne(Examcenter::class, 'inst_code', 'inst_code')
            ->whereColumn('pharmacy_exam_center.part_sem', 'pharmacy_roll_no.part_sem');
    }


    
}