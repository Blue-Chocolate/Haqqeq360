<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TestResource extends JsonResource
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
            'questions_count' => $this->questions->count(),
            'total_points' => $this->getTotalPoints(),
            'duration_minutes' => $this->duration_minutes,
            'passing_score' => $this->passing_score,
            'max_attempts' => $this->max_attempts,
            'available_from' => $this->available_from,
            'available_until' => $this->available_until,
            'user_attempts' => $this->when(
                auth()->check(),
                fn() => $this->attempts()->where('user_id', auth()->id())->count()
            ),
            'can_attempt' => $this->when(
                auth()->check(),
                fn() => $this->canUserAttempt(auth()->id())
            ),
            'is_available' => $this->isAvailable(),
        ];
    }
}