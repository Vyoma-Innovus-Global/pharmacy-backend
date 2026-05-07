<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentChoice extends Model
{
    protected $table        =   'pharmacy_choice_student';
    protected $primaryKey   =   'ch_id';
    public $timestamps      =   false;

    protected $guarded = [];

    public function student()
    {
        return $this->hasOne(User::class, "s_id", "ch_stu_id")->withDefault(function () {
            return new User();
        });
    }

    public function trade()
    {
        return $this->hasOne(Trade::class, "t_code", "ch_trade_code")->where('is_active', 1)->withDefault(function () {
            return new Trade();
        });
    }

    public function institute()
    {
        return $this->hasOne(Institute::class, "i_code", "ch_inst_code")->where('is_active', 1)->withDefault(function () {
            return new Institute();
        });
    }
}

