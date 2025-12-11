<?php

namespace App\Http\Controllers\Api\TestController;

use App\Http\Controllers\Controller;
use App\Http\Resources\TestResource;
use App\Http\Resources\TestDetailResource;
use App\Models\Test;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TestController extends Controller
{
    /**
     * Get all available tests for the authenticated user
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'testable_type' => 'nullable|string|in:bootcamp,workshop,program,course',
            'testable_id' => 'nullable|integer',
        ]);

        $query = Test::with(['testable', 'questions'])
            ->where('is_active', true);

        // Filter by testable type and id if provided
        if ($request->has('testable_type') && $request->has('testable_id')) {
            $typeMap = [
                'bootcamp' => 'App\\Models\\Bootcamp',
                'workshop' => 'App\\Models\\Workshop',
                'program' => 'App\\Models\\Program',
                'course' => 'App\\Models\\Course',
            ];

            $query->where('testable_type', $typeMap[$request->testable_type])
                  ->where('testable_id', $request->testable_id);
        }

        // Only show available tests
        $tests = $query->get()->filter(function ($test) {
            return $test->isAvailable();
        });

        return response()->json([
            'success' => true,
            'data' => TestResource::collection($tests),
        ]);
    }

    /**
     * Get tests for a specific bootcamp
     */
    public function getBootcampTests(int $bootcampId): JsonResponse
    {
        $tests = Test::where('testable_type', 'App\\Models\\Bootcamp')
            ->where('testable_id', $bootcampId)
            ->where('is_active', true)
            ->get()
            ->filter(fn($test) => $test->isAvailable());

        return response()->json([
            'success' => true,
            'data' => TestResource::collection($tests),
        ]);
    }

    /**
     * Get tests for a specific workshop
     */
    public function getWorkshopTests(int $workshopId): JsonResponse
    {
        $tests = Test::where('testable_type', 'App\\Models\\Workshop')
            ->where('testable_id', $workshopId)
            ->where('is_active', true)
            ->get()
            ->filter(fn($test) => $test->isAvailable());

        return response()->json([
            'success' => true,
            'data' => TestResource::collection($tests),
        ]);
    }

    /**
     * Get tests for a specific program
     */
    public function getProgramTests(int $programId): JsonResponse
    {
        $tests = Test::where('testable_type', 'App\\Models\\Program')
            ->where('testable_id', $programId)
            ->where('is_active', true)
            ->get()
            ->filter(fn($test) => $test->isAvailable());

        return response()->json([
            'success' => true,
            'data' => TestResource::collection($tests),
        ]);
    }

    /**
     * Get tests for a specific course
     */
    public function getCourseTests(int $courseId): JsonResponse
    {
        $tests = Test::where('testable_type', 'App\\Models\\Course')
            ->where('testable_id', $courseId)
            ->where('is_active', true)
            ->get()
            ->filter(fn($test) => $test->isAvailable());

        return response()->json([
            'success' => true,
            'data' => TestResource::collection($tests),
        ]);
    }

    /**
     * Get a specific test with all questions and complete details
     */
    public function show(int $id): JsonResponse
    {
        // Eager load all relationships to get complete test data
        $test = Test::with([
            'questions' => function ($query) {
                $query->orderBy('order', 'asc');
            },
            'questions.options',
            'testable',
            'course',
            'unit',
            'lesson'
        ])->findOrFail($id);

        // Check if test is available
        if (!$test->isAvailable()) {
            return response()->json([
                'success' => false,
                'message' => 'This test is not currently available.',
            ], 403);
        }

        // Check if user can still attempt this test
        $userId = auth()->id();
        if (!$test->canUserAttempt($userId)) {
            return response()->json([
                'success' => false,
                'message' => 'You have reached the maximum number of attempts for this test.',
            ], 403);
        }

        // Get user's previous attempts for context
        $userAttempts = $test->attempts()
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                // Test basic information
                'id' => $test->id,
                'title' => $test->title,
                'description' => $test->description,
                
                // Polymorphic relation
                // 'testable_type' => $test->testable_type,
                // 'testable_id' => $test->testable_id,
                // 'testable' => $test->testable,
                
                // Test settings
                'duration_minutes' => $test->duration_minutes,
                'passing_score' => $test->passing_score,
                'max_attempts' => $test->max_attempts,
                'shuffle_questions' => $test->shuffle_questions,
                'show_correct_answers' => $test->show_correct_answers,
                'show_results_immediately' => $test->show_results_immediately,
                
                // Scheduling
                'available_from' => $test->available_from,
                'available_until' => $test->available_until,
                'is_active' => $test->is_active,
                
                // Related entities
                'course_id' => $test->course_id,
                'course' => $test->course,
                'unit_id' => $test->unit_id,
                'unit' => $test->unit,
                'lesson_id' => $test->lesson_id,
                'lesson' => $test->lesson,
                
                // Questions with all details
                'questions' => $test->questions->map(function ($question) use ($test) {
                    return [
                        'id' => $question->id,
                        'type' => $question->type,
                        'question_text' => $question->question_text,
                        'explanation' => $question->explanation,
                        'points' => $question->points,
                        'order' => $question->order,
                        'is_required' => $question->is_required,
                        // Only show options for MCQ and True/False questions
                        'options' => in_array($question->type, ['mcq', 'true_false']) 
                            ? $question->options->map(function ($option) use ($test) {
                                return [
                                    'id' => $option->id,
                                    'option_text' => $option->option_text,
                                    // Only reveal correct answer if test settings allow
                                    // and this is not during an active attempt
                                    'is_correct' => $test->show_correct_answers ? $option->is_correct : null,
                                    'order' => $option->order ?? 0,
                                ];
                            })
                            : null,
                        'created_at' => $question->created_at,
                        'updated_at' => $question->updated_at,
                    ];
                }),
                
                // Statistics
                'total_questions' => $test->questions->count(),
                'total_points' => $test->questions->sum('points'),
                
                // User attempt information
                'user_attempts_count' => $userAttempts->count(),
                'user_attempts_remaining' => $test->max_attempts - $userAttempts->count(),
                'can_attempt' => $test->canUserAttempt($userId),
                'best_score' => $userAttempts->max('score'),
                'best_percentage' => $userAttempts->max('percentage'),
                'has_passed' => $userAttempts->contains('passed', true),
                
                // Timestamps
                'created_at' => $test->created_at,
            ],
        ]);
    }

    /**
     * Get user's attempt history for a specific test
     */
    public function getAttemptHistory(int $testId): JsonResponse
    {
        $test = Test::findOrFail($testId);

        $attempts = $test->attempts()
            ->where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'test_title' => $test->title,
                'max_attempts' => $test->max_attempts,
                'attempts_used' => $attempts->count(),
                'can_attempt_again' => $test->canUserAttempt(auth()->id()),
                'attempts' => $attempts->map(function ($attempt) {
                    return [
                        'id' => $attempt->id,
                        'attempt_number' => $attempt->attempt_number,
                        'status' => $attempt->status->value,
                        'score' => $attempt->score,
                        'total_points' => $attempt->total_points,
                        'percentage' => $attempt->percentage,
                        'passed' => $attempt->passed,
                        'started_at' => $attempt->started_at,
                        'submitted_at' => $attempt->submitted_at,
                        'graded_at' => $attempt->graded_at,
                    ];
                }),
            ],
        ]);
    }
}