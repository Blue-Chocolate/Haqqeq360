<?php

namespace App\Models;

use App\Enums\QuestionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'test_attempt_id',
        'question_id',
        'selected_option_id',
        'written_answer',
        'is_correct',
        'points_earned',
        'feedback',
        'graded_by',
        'graded_at',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
        'points_earned' => 'decimal:2',
        'graded_at' => 'datetime',
    ];

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(TestAttempt::class, 'test_attempt_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function selectedOption(): BelongsTo
    {
        return $this->belongsTo(QuestionOption::class, 'selected_option_id');
    }

    public function gradedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }

    // Auto-grade MCQ and True/False answers
    public function autoGrade(): void
    {
        if (!$this->question->isAutoGradable()) {
            return;
        }

        $correctOption = $this->question->getCorrectOption();
        
        if (!$correctOption) {
            return;
        }

        $this->is_correct = $this->selected_option_id === $correctOption->id;
        $this->points_earned = $this->is_correct ? $this->question->points : 0;
        $this->graded_at = now();
        $this->save();
    }

    // Manual grading for written answers
    public function manualGrade(float $points, ?string $feedback = null, ?int $gradedBy = null): void
    {
        $this->points_earned = min($points, $this->question->points);
        $this->is_correct = $this->points_earned === $this->question->points;
        $this->feedback = $feedback;
        $this->graded_by = $gradedBy;
        $this->graded_at = now();
        $this->save();
    }
}