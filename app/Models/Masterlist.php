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
    protected $fillable = [
        'user_id',
        'scid_number',
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'birth_date',       // In-rename mula birthdate
        'age',
        'sex',              // In-rename mula gender
        'civil_status',
        'citizenship',
        'birth_place',      // In-rename mula birthplace
        'address',
        'barangay',
        'city_municipality',
        'province',
        'district',
        'email',
        'living_arrangement',
        'is_pensioner',
        'pension_amount',
        'has_regular_support',
        'pension_source_gsis',
        'pension_source_sss',
        'pension_source_afpslai',
        'pension_source_others',
        'has_permanent_income',
        'permanent_income_source',
        'support_type_cash',
        'support_cash_amount',
        'support_cash_frequency',
        'support_type_inkind',
        'support_inkind_details',
        'has_illness',
        'illness_details',
        'hospitalized_last_6_months',
        'contact_number',
        'reg_attachments',
        'registration_type',
        'vital_status',
        'date_of_death',
        'registration_date', // In-rename mula date_submitted
        'date_reviewed',
        'reviewed_by',
        'id_status',         // Ang status sa masterlist
        'document_path'
    ];

    /**
     * Type Casting
     * Para sa tamang format ng boolean at date fields.
     */
    protected $casts = [
        'birth_date' => 'date',
        'registration_date' => 'datetime',
        'date_of_death' => 'date',
        'date_reviewed' => 'datetime',
        'date_created' => 'datetime',
        'last_updated' => 'datetime',
        'is_pensioner' => 'boolean',
        'has_regular_support' => 'boolean',
        'has_permanent_income' => 'boolean',
        'has_illness' => 'boolean',
        'hospitalized_last_6_months' => 'boolean',
        'pension_source_gsis' => 'boolean',
        'pension_source_sss' => 'boolean',
        'pension_source_afpslai' => 'boolean',
        'support_type_cash' => 'boolean',
        'support_type_inkind' => 'boolean',
        'age' => 'integer'
    ];
}