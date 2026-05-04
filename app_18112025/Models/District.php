<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class District extends Model
{
    protected $table        =   'pharmacy_district_master';
    protected $primaryKey   =   'district_id_pk';
    public $timestamps      =   false;
 
    protected $guarded = [];
    public function state()
    {
        return $this->hasOne('App\Models\State', "state_id_pk", "state_id_fk")->withDefault(function () {
            return new State();
        });
    }
}
