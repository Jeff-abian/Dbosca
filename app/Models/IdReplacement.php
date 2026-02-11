<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IdReplacement extends Model
{
    protected $table = 'id_replacements';
    protected $primaryKey = 'replacement_id';
    
    // I-disable ang default timestamps kung wala kang 'created_at'/'updated_at'
    // O i-map sila kung mayroon kang specific columns para dito
    public $timestamps = false; 

    protected $fillable = [
        'citizen_id', 'user_id', 'old_id_number', 'new_id_number', 'gender','scid_number',
        'last_name', 'first_name', 'middle_name', 'suffix', 'birthdate',
        'place_of_birth', 'age', 'house_no', 'street', 'barangay',
        'city_municipality', 'province', 'district', 'citizenship',
        'senior_contact_number', 'civil_status', 'emergency_contact_person',
        'willing_member', 'contact_number', 'status', 'approved_date',
        'submitted_date', 'released_date', 'issued_date', 'photo_url'
    ];

    // Para sa automatic date formatting
    protected $dates = [
        'birthdate', 'approved_date', 'submitted_date', 
        'released_date', 'issued_date'
    ];
}