<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Test extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'testable_type',
        'testable_id',
        'duration_minutes',
        'passing_score',
        'max_attempts',
        'shuffle_questions',
        'show_correct_answers',
        'show_results_immediately',
        'available_from',
        'available_until',
        'is_active',
    ];

    protected $casts = [
        'passing_score' => 'decimal:2',
        'shuffle_questions' => 'boolean',
        'show_correct_answers' => 'boolean',
        'show_results_immediately' => 'boolean',
        'available_from' => 'datetime',
        'available_until' => 'datetime',
        'is_active' => 'boolean',
    ];

    // Polymorphic relationship
    public function testable(): MorphTo
    {
        return $this->morphTo();
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('order');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(TestAttempt::class);
    }

    // Helper methods
    public function isAvailable(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now();

        if ($this->available_from && $now->isBefore($this->available_from)) {
            return false;
        }

        if ($this->available_until && $now->isAfter($this->available_until)) {
            return false;
        }

        return true;
    }

    public function getTotalPoints(): float
    {
        return $this->questions()->sum('points');
    }

    public function canUserAttempt(int $userId): bool
    {
        $attemptCount = $this->attempts()
            ->where('user_id', $userId)
            ->count();

        return $attemptCount < $this->max_attempts;
    }
}