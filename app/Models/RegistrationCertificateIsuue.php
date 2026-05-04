<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegistrationCertificateIssue extends Model
{
    protected $table        =   'registration_certificate_issue';
    protected $primaryKey   =   'id';
    public $timestamps      =   false;
 
    protected $guarded = [];
   
}
