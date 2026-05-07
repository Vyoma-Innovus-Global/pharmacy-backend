<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    use HasFactory;
    protected $table = 'pharmacy_schedule_master';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $guarded = [];
}
