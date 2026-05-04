<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $table = "pharmacy_payment_final";
    public $timestamps = false;

    protected $guarded = [];

    public function fees()
    {
        return $this->hasMany(Fees::class, 'fees_order_id', 'pmnt_order_id');
    }

    // table update
    public function updatediscipline()
    {
        return $this->hasOne(Discipline::class, "id", "discipline_code");
    }
}
