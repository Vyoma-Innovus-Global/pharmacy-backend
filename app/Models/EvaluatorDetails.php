<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvaluatorDetails extends Model
{
    use HasFactory;

    protected $table = 'pharmacy_evaluator';
    protected $primaryKey   =   'id';
    public $timestamps = false;
    protected $guarded = [];

    public function roles()
    {
        return $this->hasOne(Role::class, "role_id", "ev_role_id");
    }
    public function allocationData()
    {
        return $this->hasOne(EvaluatorAllocation::class, 'evaluator_id', 'ev_id');
    }


    public function scutinizerData()
    {
        return $this->hasOne(EvaluatorAllocation::class, 'evaluator_id', 'ev_id')->where('type', 'SCRUTINIZER');
    }

    public function examinerAllocation()
    {
        return $this->hasOne(EvaluatorAllocation::class, 'examiner_id', 'ev_id');
    }

    public function headExaminerAllocation()
    {
        return $this->hasOne(EvaluatorAllocation::class, 'head_examiner_id', 'ev_id');
    }

    public function scrutinizerAllocation()
    {
        return $this->hasOne(EvaluatorAllocation::class, 'scrutinizer_id', 'ev_id');
    }

    public function credential()
    {
        return $this->hasOne(EvaluatorCredential::class, 'evaluator_id', 'ev_id');
    }


}
