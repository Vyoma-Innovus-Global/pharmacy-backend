<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvaluatorAllocation extends Model
{
    use HasFactory;

    protected $table = 'pharmacy_evaluator_allocations';
    public $timestamps = false;
    protected $guarded = [];

    public function examiner()
    {
        return $this->hasOne(EvaluatorDetails::class, 'ev_id', 'examiner_id');
    }

    public function headExaminer()
    {
        return $this->hasOne(EvaluatorDetails::class, 'ev_id', 'head_examiner_id');
    }

    public function scrutinizer()
    {
        return $this->hasOne(EvaluatorDetails::class, 'ev_id', 'scrutinizer_id');
    }
    

    
}
