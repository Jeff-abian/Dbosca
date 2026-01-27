<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    

    // 1. Custom Primary Key
    protected $primaryKey = 'id'; 
    public $incrementing = true;
    // 2. Custom Date Columns (Laravel's default 'updated_at' is not used)

    
    //const UPDATED_AT = 'updated_at';
    
    // You must set this to false to tell Laravel not to look for the 'updated_at' column
    // However, since you have 'date_modified', we use the constant above.
    // protected $guarded = []; // Or define $fillable as standard practice

    protected $fillable = [
        'username',
        'name',
        'email',
        'password',
        'role',
        
         // Include the custom column
    ];
     // 3. Relationships
    public function roleRelation()
    {
        // belongsTo(RelatedModel, foreign_key_on_users_table, owner_key_on_roles_table)
        // Siguraduhin na 'role' ang column name sa users table mo na naglalaman ng Role ID
        return $this->belongsTo(Role::class, 'role', 'role_id');
    }
}
    
    // ... rest of the file

