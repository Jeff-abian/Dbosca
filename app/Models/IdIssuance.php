<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class IdIssuance extends Model
{
    use HasFactory;

    protected $table = 'id_issuance';

    // In-update mula 'issuance_id' patungong 'id' base sa iyong bagong columns
    protected $primaryKey = 'id'; 
    public $incrementing = true;

    // Custom timestamps mapping
    const CREATED_AT = 'date_created';
    const UPDATED_AT = 'last_updated';

    /**
     * Mass Assignable Attributes
     * Inayos base sa iyong pinakabagong column list para sa iwas Error 1364.
     */
    protected $fillable = [
        'citizen_id',
        'user_id',
        'scid_number',
        'gender',
        'contact_number',
        'last_name',
        'first_name',
        'middle_name',
        'suffix',
        'birthdate',
        'place_of_birth',
        'age',
        'house_no',
        'street',
        'barangay',
        'city_municipality',
        'province',
        'district',
        'citizenship',
        'civil_status',
        'emergency_contact_person',
        'emergency_contact_number',
        'willing_member',
        'id_request_type',       // Bagong column
        'id_modality',           // Bagong column
        'application_date',      // In-rename mula 'submitted_date'
        'id_application_status', // Bagong column
        'id_status',             // Ang status na ginagamit sa sync
        'date_reviewed',
        'rejection_remarks',
        'released_date',
        'id_expiration_date',
        'photo_url',
        'req1_url',
        'req2_url'
    ];

    /**
     * Type Casting
     * Sinisiguro nito na ang mga date fields ay Carbon objects.
     */
    protected $casts = [
        'birthdate'          => 'date',
        'application_date'   => 'datetime',
        'date_reviewed'      => 'datetime',
        'released_date'      => 'date',
        'id_expiration_date' => 'date',
        'date_created'       => 'datetime',
        'last_updated'       => 'datetime',
    ];

    // Relationship sa User model
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Relationship sa Masterlist model
    public function masterlist()
    {
        // Gagamit ng 'citizen_id' bilang link sa pagitan ng dalawang table
        return $this->belongsTo(Masterlist::class, 'citizen_id', 'citizen_id');
    }
}