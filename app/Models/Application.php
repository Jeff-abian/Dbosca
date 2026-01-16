<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    use HasFactory;

    /**
     * CUSTOM DATABASE CONFIGURATION
     */
    
    // 1. Table name
    protected $table = 'applications';

    // 2. Primary Key is 'application_id' instead of 'id'
    protected $primaryKey = 'id';

    // 3. Enable timestamps and map custom columns
    public $timestamps = true; 
    const CREATED_AT = 'date_created';
    const UPDATED_AT = 'last_updated';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
    'user_id',
    'citizen_id',
    'last_name',
    'first_name',
    'middle_name',
    'suffix',
    'email',
    'citizenship',
    'house_no',
    'street',
    'barangay',
    'city_municipality',
    'province',
    'district',
    'age',
    'gender',
    'civil_status',
    'birthdate',
    'birthplace',
    'living_arrangement',
    'is_pensioner',
    'pension_source',
    'pension_amount',
    'has_illness',
    'illness_details',
    'document_url',
    'application_type',
    'status',
];


    /**
     * The attributes that should be cast to native types.
     */
    protected $casts = [
        'birthdate' => 'date',
        'date_submitted' => 'datetime',
        'date_reviewed' => 'datetime',
        'date_created' => 'datetime',
        'last_updated' => 'datetime',
        'age' => 'integer'
    ];
}