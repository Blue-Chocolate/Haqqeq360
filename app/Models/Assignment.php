<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Assignment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'course_id',
        'lesson_id',
        'title',
        'description',
        'due_date',
        'max_score',
        'attachment_path',
        'published',
        'unit_id',
    ];

    protected $casts = [
        'due_date' => 'datetime',
        'max_score' => 'decimal:2',
        'published' => 'boolean',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }

    public function submissions()
    {
        return $this->hasMany(Submission::class);
    }
    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
}
