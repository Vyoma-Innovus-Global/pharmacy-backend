<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pharmacyfees extends Model
{
    use HasFactory;
    protected $table = 'pharmacy_fees';
    protected $fillable = [
        'id',
        'anual',
        'course',
        'fees_type',
        'fees_amount',
        'description',
        'gender',
    ];
}
