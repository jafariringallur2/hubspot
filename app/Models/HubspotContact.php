<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HubspotContact extends Model
{
    use HasFactory;

    protected $fillable = [
        'hubspot_account_id',
        'hubspot_contact_id', 
        'user_id', 
        'first_name', 
        'last_name', 
        'email', 
    ];
}
