<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $fillable = [
        'student_id',
        'name',
    ];

    public function role()
    {           
        return $this->belongsTo(Role::class);
    }
}
