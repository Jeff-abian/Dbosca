<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Role; 
use Illuminate\Foundation\Auth\User as Authenticatable;

class Role extends Model
{
    use HasFactory;

    // 1. Siguraduhin na tama ang pangalan ng table (kung hindi 'roles', i-uncomment ito)
    // protected $table = 'roles';

    // 2. Kung hindi 'id' ang primary key mo sa roles table
    // protected $primaryKey = 'role_id';

    // 3. I-define ang kabaligtaran na relasyon sa User
    protected $primaryKey = 'role_id';
    protected $table = 'roles';
    public function users()
    {
        return $this->hasMany(User::class, 'role', 'role_id');
    }
// Siguraduhin na nandito ito:

// ... (ibang imports)

}