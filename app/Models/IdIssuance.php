<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IdIssuance extends Model
{
    protected $table = 'id_issuance';
    protected $primaryKey = 'issuance_id';
    
    // I-set sa false kung wala kang 'created_at' at 'updated_at' columns
    public $timestamps = false;

    protected $fillable = [
        'citizen_id',
        'id_number',
        'issued_date',
        'released_date',
        'status'
    ];

    // Cast dates para maging Carbon instances (madaling i-format)
    protected $dates = ['issued_date', 'released_date'];
}