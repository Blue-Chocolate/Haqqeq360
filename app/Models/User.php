<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable  {
    use HasApiTokens, HasFactory, Notifiable, HasRoles,SoftDeletes;

protected $fillable = [
    'first_name',
    'second_name', 
    'email',
    'phone',
    'password',
    'role',
    'bio',
    'avatar',
    'email_verified_at', // Add if you want it mass-assignable
    'api_token' // Add if you want it mass-assignable
];
    public function enrollments() { return $this->hasMany(Enrollment::class); }
    public function courses() { return $this->hasMany(Course::class, 'instructor_id'); }
    public function bootcamps() { return $this->hasMany(Bootcamp::class, 'instructor_id'); }

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
// In App\Models\User.php
public function getNameAttribute(): string
{
    return trim($this->first_name . ' ' . $this->second_name);
}


}