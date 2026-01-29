<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Masterlist extends Model
{
    // Idagdag ito para sabihing hindi mo ginagamit ang default timestamps
    public $timestamps = false;
    
    // Siguraduhin din na naka-set ang primary key kung hindi ito 'id'
    protected $primaryKey = 'citizen_id';
    protected $table = 'masterlist';
    
    
    // Set the primary key if it is not 'id' (e.g., citizen_id)
   
    public $incrementing = true; // Set to true if it's an auto-incrementing INT

    // Map Laravel's timestamps to your existing columns
    const CREATED_AT = 'date_created';
    const UPDATED_AT = 'last_updated';

    protected $fillable = [
        'user_id', 'last_name', 'first_name', 'middle_name', 'suffix',
        'citizenship', 'house_no', 'street', 'barangay', 'city_municipality',
        'province', 'district', 'age', 'gender', 'civil_status', 'birthdate',
        'birthplace', 'living_arrangement', 'date_submitted', 
        'applicant_signature_image', 'received_by', 'status', 'email'
    ];
}