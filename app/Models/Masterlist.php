<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Masterlist extends Model
{
    use HasFactory;

    protected $table = 'masterlist';
    protected $primaryKey = 'citizen_id';

    // Gawing true ito dahil auto-increment ang citizen_id mo sa DB
    public $incrementing = true;

    // Gawing true ito para gumana ang custom columns ng timestamps
    public $timestamps = true;

    // I-map ang default timestamps sa column names mo sa MySQL
    const CREATED_AT = 'date_created';
    const UPDATED_AT = 'last_updated';

    protected $fillable = [
    'user_id', 'id_status', 'scid_number', 'first_name', 'last_name','middle_name','age',
    'email', 'contact_number', 'birthdate', 'birthplace', 'gender', 
    'civil_status', 'citizenship', 'house_no', 'street', 'barangay', 
    'city_municipality', 'province', 'district', 'status', 'date_submitted'
];
}