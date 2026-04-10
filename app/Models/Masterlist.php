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
    'registration_date','document', // --- DAPAT NANDITO ITONG MGA ITO PARA MAG-SYNC ---
    'is_pensioner',
    'pension_source_gsis',
    'pension_source_sss',
    'pension_source_afpslai',
    'pension_source_others',
    'date_of_death',
    'pension_amount',
    'has_permanent_income',
    'permanent_income_source',
    'has_regular_support',
    'support_type_cash',
    'reviewed_by',
    'support_cash_amount',
    'support_cash_frequency',
    'support_type_inkind', // <--- I-check kung 'kind_support_details' o 'support_inkind_details' ang nasa DB
    'has_illness',
    'illness_details',
    'hospitalized_last_6_months',
    
    'id_status', 'document', 'registration_date'
];
    protected $casts = [
        'is_pensioner' => 'boolean',
        'has_illness' => 'boolean',
        // ... iba pang TINYINT columns
    ];
public function application()
{
    // Sinasabi nito na ang Masterlist ay "connected" sa isang Application
    return $this->belongsTo(Application::class, 'application_id');
}
}