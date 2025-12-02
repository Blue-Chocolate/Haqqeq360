<?php

namespace App\Models;

use App\Enums\QuestionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'test_id',
        'type',
        'question_text',
        'explanation',
        'points',
        'order',
        'is_required',
    ];

    protected $casts = [
        'type' => QuestionType::class,
        'points' => 'decimal:2',
        'is_required' => 'boolean',
    ];

    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(QuestionOption::class)->orderBy('order');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(TestAnswer::class);
    }

    // Helper methods
    public function getCorrectOption(): ?QuestionOption
    {
        return $this->options()->where('is_correct', true)->first();
    }

    public function isAutoGradable(): bool
    {
        return in_array($this->type, [QuestionType::MCQ, QuestionType::TRUE_FALSE]);
    }

    public function requiresManualGrading(): bool
    {
        return $this->type === QuestionType::WRITTEN;
    }
}