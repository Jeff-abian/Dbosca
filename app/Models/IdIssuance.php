<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IdIssuance extends Model
{
    protected $table = 'id_issuance';
    protected $primaryKey = 'issuance_id'; 

    // Custom timestamps mapping
    const CREATED_AT = 'date_created';
    const UPDATED_AT = 'last_updated';

    protected $fillable = [
        'issuance_id','citizen_id', 'id_number','user_id','senior_contact_number', 'last_name', 'first_name', 'middle_name', 'suffix','birthdate','place_of_birth',
        'citizenship','house_no','street','barangay','city_municipality',
        'province', 'district', 'age', 'gender', 'civil_status','emergency_contact_person','contact_number','willing_member',
        'birthplace', 'living_arrangement', 'date_submitted', 
        'received_by', 'status', 'email','willing_member','approved_date','submitted_date','approved_at','issued_date','released_date','photo_url','req1_url', 'req2_url'
    ];

    // Relationship sa User model
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Relationship sa Masterlist model
    public function masterlist()
    {
        return $this->belongsTo(Masterlist::class, 'citizen_id', 'citizen_id');
    }
}