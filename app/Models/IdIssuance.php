<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IdIssuance extends Model
{
    use HasFactory;

    protected $table = 'id_issuance';

    public $timestamps = false; // Custom timestamp column names used in schema

    protected $fillable = [
        'emergency_contact_person',
        'emergency_contact_number',
        'willing_member',
        'id_request_type',
        'id_modality',
        'application_date',
        'id_application_status',
        'id_status',
        'date_reviewed',
        'rejection_remarks',
        'released_date',
        'id_expiration_date',
        'photo_url',
        'scid_number',
        'citizen_id',
        'user_id'
    ];

    protected $casts = [
        'willing_member' => 'boolean',
        'application_date' => 'date',
        'date_reviewed' => 'date',
        'released_date' => 'date',
        'id_expiration_date' => 'date',
        'date_created' => 'datetime',
        'last_updated' => 'datetime',
    ];

    const CREATED_AT = 'date_created';
    const UPDATED_AT = 'last_updated';
    
    
        /**
        * Get the user/citizen details associated with the issuance.
        */
        public function masterlist(): BelongsTo
    {
        // Assuming 'user_id' in id_issuance maps to 'user_id' in masterlist
        return $this->belongsTo(Masterlist::class, 'user_id', 'user_id');
    }
}