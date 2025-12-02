<?php

namespace App\Models;

use App\Enums\TestAttemptStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TestAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'test_id',
        'user_id',
        'status',
        'started_at',
        'submitted_at',
        'graded_at',
        'score',
        'total_points',
        'percentage',
        'passed',
        'attempt_number',
        'question_order',
    ];

    protected $casts = [
        'status' => TestAttemptStatus::class,
        'started_at' => 'datetime',
        'submitted_at' => 'datetime',
        'graded_at' => 'datetime',
        'score' => 'decimal:2',
        'total_points' => 'decimal:2',
        'percentage' => 'decimal:2',
        'passed' => 'boolean',
        'question_order' => 'array',
    ];

    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(TestAnswer::class);
    }

    // Helper methods
    public function isInProgress(): bool
    {
        return $this->status === TestAttemptStatus::IN_PROGRESS;
    }

    public function isSubmitted(): bool
    {
        return $this->status === TestAttemptStatus::SUBMITTED;
    }

    public function isGraded(): bool
    {
        return $this->status === TestAttemptStatus::GRADED;
    }

    public function calculateScore(): void
    {
        $totalPoints = $this->test->getTotalPoints();
        $earnedPoints = $this->answers()->sum('points_earned');

        $this->score = $earnedPoints;
        $this->total_points = $totalPoints;
        $this->percentage = $totalPoints > 0 ? ($earnedPoints / $totalPoints) * 100 : 0;
        $this->passed = $this->percentage >= $this->test->passing_score;
        $this->save();
    }

    public function hasExpired(): bool
    {
        if (!$this->test->duration_minutes || !$this->started_at) {
            return false;
        }

        $expiryTime = $this->started_at->addMinutes($this->test->duration_minutes);
        return now()->isAfter($expiryTime);
    }

    public function getRemainingTime(): ?int
    {
        if (!$this->test->duration_minutes || !$this->started_at) {
            return null;
        }

        $expiryTime = $this->started_at->addMinutes($this->test->duration_minutes);
        $remainingSeconds = now()->diffInSeconds($expiryTime, false);

        return max(0, $remainingSeconds);
    }
}