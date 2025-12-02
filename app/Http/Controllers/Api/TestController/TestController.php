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
     * Get a specific test with questions (without revealing correct answers)
     */
    public function show(int $id): JsonResponse
    {
        $test = Test::with(['questions.options', 'testable'])
            ->findOrFail($id);

        // Check if test is available
        if (!$test->isAvailable()) {
            return response()->json([
                'success' => false,
                'message' => 'This test is not currently available.',
            ], 403);
        }

        // Check if user can still attempt this test
        if (!$test->canUserAttempt(auth()->id())) {
            return response()->json([
                'success' => false,
                'message' => 'You have reached the maximum number of attempts for this test.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => new TestDetailResource($test),
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