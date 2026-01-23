<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IdRenewals extends Model
{
   protected $table = 'id_renewals';
    protected $primaryKey = 'renewal_id';
    
    // I-disable ang default timestamps kung gagamit ka ng sariling date columns
    public $timestamps = false; 

    protected $fillable = [
        'citizen_id', 'user_id', 'old_id_number', 'new_id_number', 'gender',
        'senior_contact_number', 'last_name', 'first_name', 'middle_name', 'suffix',
        'birthdate', 'place_of_birth', 'age', 'house_no', 'street', 'barangay',
        'city_municipality', 'province', 'district', 'citizenship', 'civil_status',
        'emergency_contact_person', 'status', 'contact_number', 'willing_member',
        'submitted_date', 'approved_date', 'released_date', 'issued_date', 'photo_url'
    ];

    // Cast dates para magamit ang Carbon features
    protected $dates = [
        'birthdate', 'submitted_date', 'approved_date', 
        'released_date', 'issued_date'
    ]; 
}
