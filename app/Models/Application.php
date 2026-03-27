<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    use HasFactory;

    // 1. Table name
    protected $table = 'applications';

    // 2. Primary Key
    protected $primaryKey = 'id';

    // 3. Timestamps Configuration
    public $timestamps = true; 
    const CREATED_AT = 'date_created';
    const UPDATED_AT = 'last_updated';

    /**
     * Mass Assignable Attributes
     * In-update base sa iyong bagong listahan ng columns.
     */
    protected $fillable = [
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'birth_date',      // In-rename mula birthdate
        'age',
        'sex',             // In-rename mula gender
        'civil_status',
        'citizenship',
        'birth_place',     // In-rename mula birthplace
        'address',
        'barangay',
        'city_municipality',
        'district',
        'province',
        'email',
        'living_arrangement',
        'is_pensioner',
        'pension_source_gsis',
        'pension_source_sss',
        'pension_source_afpslai',
        'pension_source_others',
        'pension_amount',
        'has_permanent_income',
        'permanent_income_source',
        'has_regular_support',
        'support_type_cash',
        'support_cash_amount',
        'support_cash_frequency',
        'support_type_inkind',
        'kind_support_details',
        'has_illness',
        'illness_details',
        'hospitalized_last_6_months',
        'contact_number',
        'document',
        'registration_type',
        'reg_status',       // In-rename mula status
        'rejection_remarks',
        'encoded_by',
        'registration_date',
        'date_reviewed',
        'reviewed_by',
        'applicationscol'
    ];

    /**
     * Type Casting
     * Sinisiguro nito na ang data ay nasa tamang format paglabas ng database.
     */
    protected $casts = [
        'birth_date' => 'date',
        'registration_date' => 'datetime',
        'date_reviewed' => 'datetime',
        'date_created' => 'datetime',
        'last_updated' => 'datetime',
        'age' => 'integer',
        'is_pensioner' => 'boolean',
        'has_permanent_income' => 'boolean',
        'has_regular_support' => 'boolean',
        'has_illness' => 'boolean',
        'hospitalized_last_6_months' => 'boolean',
        'pension_source_gsis' => 'boolean',
        'pension_source_sss' => 'boolean',
        'pension_source_afpslai' => 'boolean',
        'support_type_cash' => 'boolean',
        'support_type_inkind' => 'boolean',
    ];
}