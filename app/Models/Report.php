<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Report extends Model {
    protected $fillable = ['user_id','enrollable_type','enrollable_id','completion_rate','grade_avg','feedback_summary'];
   public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the enrollable model (Course, Bootcamp, Workshop, Program)
     */
    public function enrollable()
    {
        return $this->morphTo();
    }

    /**
     * Scope to get reports for a specific user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get completed reports
     */
    public function scopeCompleted($query)
    {
        return $query->where('completion_rate', 100);
    }

    /**
     * Check if report is completed
     */
    public function isCompleted()
    {
        return $this->completion_rate >= 100;
    }

    /**
     * Get grade letter based on average
     */
    public function getGradeLetterAttribute()
    {
        if (!$this->grade_avg) return null;

        return match(true) {
            $this->grade_avg >= 90 => 'A',
            $this->grade_avg >= 80 => 'B',
            $this->grade_avg >= 70 => 'C',
            $this->grade_avg >= 60 => 'D',
            default => 'F'
        };
    }}
