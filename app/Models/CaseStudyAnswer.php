<?php
// app/Models/CaseStudyAnswer.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CaseStudyAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'case_study_id',
        'learner_id',
        'answer_text',
        'submitted_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
    ];

    public function caseStudy()
    {
        return $this->belongsTo(CaseStudy::class);
    }

    public function learner()
    {
        return $this->belongsTo(User::class, 'learner_id');
    }

    public function files()
    {
        return $this->hasMany(CaseStudyAnswerFile::class, 'answer_id');
    }
}
