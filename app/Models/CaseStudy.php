<?php

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
        'course_id',
        'unit_id',
        'lesson_id',
        'guidelines',
        'attachment',
        'max_score',
        'passing_score',
        'max_attempts',
        'allow_late_submission',
        'show_model_answer',
        'peer_review_enabled',
        'model_answer',
        'available_from',
        'available_until',
    ];

    protected $casts = [
        'status' => 'string',
        'duration' => 'integer',
        'max_score' => 'integer',
        'passing_score' => 'integer',
        'max_attempts' => 'integer',
        'allow_late_submission' => 'boolean',
        'show_model_answer' => 'boolean',
        'peer_review_enabled' => 'boolean',
        'available_from' => 'datetime',
        'available_until' => 'datetime',
    ];

    public function instructor()
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
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

    public function scopeAvailable($query)
    {
        return $query->where('status', 'open')
            ->where(function ($q) {
                $q->whereNull('available_from')
                  ->orWhere('available_from', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('available_until')
                  ->orWhere('available_until', '>=', now());
            });
    }

    public function isAvailable(): bool
    {
        if ($this->status !== 'open') {
            return false;
        }

        if ($this->available_from && $this->available_from->isFuture()) {
            return false;
        }

        if ($this->available_until && $this->available_until->isPast()) {
            return false;
        }

        return true;
    }

    public function canSubmit(): bool
    {
        if (!$this->isAvailable()) {
            return false;
        }

        if (!$this->allow_late_submission && $this->available_until && $this->available_until->isPast()) {
            return false;
        }

        return true;
    }
}