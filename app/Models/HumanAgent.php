<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HumanAgent extends Model
{
    protected $fillable = [
        'department_id', 'name', 'email', 'phone', 
        'work_hours', 'cc_emails', 'is_active'
    ];

    protected $casts = [
        'work_hours' => 'array',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }
}