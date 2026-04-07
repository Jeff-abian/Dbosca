<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Masterlist extends Model
{
    use HasFactory;

    protected $table = 'masterlist';
    protected $primaryKey = 'citizen_id';

    // Auto-increment ang citizen_id sa iyong database
    public $incrementing = true;

    // Custom columns para sa timestamps
    public $timestamps = true;
    const CREATED_AT = 'date_created';
    const UPDATED_AT = 'last_updated';

    /**
     * Mass Assignable Attributes
     * In-update base sa pinakabagong column list.
     */
    // app/Models/Masterlist.php
protected $fillable = [
    'application_id', 'user_id','username','scid_number', 'first_name', 'middle_name', 'temp_password','registration_type', // Halimbawa: New, Renewal, Replacement
    'date_reviewed',
    'last_name', 'suffix', 'birth_date', 'age', 'sex', 'civil_status', 
    'citizenship', 'birth_place', 'address', 'barangay', 'city_municipality', 
    'district', 'province', 'email', 'contact_number', 'living_arrangement', 
    'is_pensioner', 'pension_amount', 'has_illness', 'id_status', 
    'registration_date','document'
];

public function application()
{
    // Sinasabi nito na ang Masterlist ay "connected" sa isang Application
    return $this->belongsTo(Application::class, 'application_id');
}
}