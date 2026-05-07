<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Institute extends Model
{
    protected $table        =   'institute_master';
    protected $primaryKey   =   'i_id';
    public $timestamps      =   false;

    protected $guarded = [];
    
    public function students()
    {
        return $this->hasMany(Registerstudent::class, 's_inst_code', 'i_code');
    }
    public function enrollments()
    {
        return $this->hasMany(pharmacy_register_student_final::class, 's_inst_code', 'i_code');
    }

    public function selfs()
    {
        return $this->hasMany(MgmtStudent::class, 's_inst_code', 'i_code')->where('seat_type', 'SELF');
    }

    public function managements()
    {
        return $this->hasMany(MgmtStudent::class, 's_inst_code', 'i_code')->where('seat_type', 'MANAGEMENT');
    }

    public function spots()
    {
        return $this->hasMany(SpotStudent::class, 'spot_inst_code', 'i_code');
    }

    public function managementSeat()
    {
        return $this->hasOne(ManagementSeat::class, 'sm_inst_code', 'i_code');
    }

    public function selfSeat()
    {
        return $this->hasOne(PrivateSeat::class, 'sm_inst_code', 'i_code');
    }
}