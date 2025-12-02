<?php

namespace App\Http\Resources;

use App\Enums\QuestionType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TestDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'testable_type' => class_basename($this->testable_type),
            'testable' => [
                'id' => $this->testable_id,
                'title' => $this->testable?->title,
            ],
            'duration_minutes' => $this->duration_minutes,
            'passing_score' => $this->passing_score,
            'max_attempts' => $this->max_attempts,
            'shuffle_questions' => $this->shuffle_questions,
            'show_correct_answers' => $this->show_correct_answers,
            'show_results_immediately' => $this->show_results_immediately,
            'available_from' => $this->available_from,
            'available_until' => $this->available_until,
            'total_points' => $this->getTotalPoints(),
            'questions_count' => $this->questions->count(),
            'user_attempts' => $this->when(
                auth()->check(),
                fn() => $this->attempts()->where('user_id', auth()->id())->count()
            ),
            'can_attempt' => $this->when(
                auth()->check(),
                fn() => $this->canUserAttempt(auth()->id())
            ),
            // Preview of questions (without correct answers)
            'questions_preview' => $this->questions->map(function ($question) {
                return [
                    'type' => $question->type->label(),
                    'points' => $question->points,
                ];
            }),
        ];
    }
}