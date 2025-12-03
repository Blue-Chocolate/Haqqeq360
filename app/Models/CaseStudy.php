<?php

// app/Models/CaseStudy.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CaseStudy extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'status',
        'duration',
        'content',
        'instructor_id',
    ];

    protected $casts = [
        'status' => 'string',
        'duration' => 'integer',
    ];

    public function instructor()
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function answers()
    {
        return $this->hasMany(CaseStudyAnswer::class);
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }
}