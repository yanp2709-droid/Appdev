<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Teacher extends Model
{
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
    ];
        public function role()
        {           
            return $this->belongsTo(Role::class);
        }
}
