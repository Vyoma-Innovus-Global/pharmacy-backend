<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class EvaluatorCredential extends Model
{
    protected $table        =   'pharmacy_evaluator_credentials';
    protected $primaryKey   =   'id';
    public $timestamps      =   false;

    protected $guarded = [];

    
    
}