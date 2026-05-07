<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use DB;
use Session;

class BlockMunicipality extends Model
{
    protected $table        =   'pharmacy_block_municipalities';
    protected $primaryKey   =   'id';
    public $timestamps      =   false;

    // protected $fillable = [
    //     'district_id_fk', 'block_municipality_name', 'block_muni_flag', 'active_status', 'schcd', 'subdiv_id_fk', 'lgd_code'
    // ];
    // public function district()
    // {
    //     return $this->hasMany(District::class, 'district_id_pk', 'district_id_fk');
    // }
    
    
}
