<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScholarshipRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'applicant_name',
        'number_of_participants',
        'program_type',
        'skills_and_needs',
        'attachments',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}