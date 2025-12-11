<?php

namespace App\Http\Controllers\Api\AssignmentController;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AssignmentController extends Controller
{
    /**
     * Get assignments by lesson (course/unit/lesson hierarchy)
     */
    public function getByLesson(int $courseId, int $unitId, int $lessonId): JsonResponse
    {
        $assignments = Assignment::with(['course', 'unit', 'lesson'])
            ->where('course_id', $courseId)
            ->where('unit_id', $unitId)
            ->where('lesson_id', $lessonId)
            ->where('published', true)
            ->orderBy('due_date', 'asc')
            ->get();

        $userId = auth()->id();
        $submissions = Submission::where('user_id', $userId)
            ->whereIn('assignment_id', $assignments->pluck('id'))
            ->get()
            ->keyBy('assignment_id');

        return response()->json([
            'success' => true,
            'data' => $assignments->map(function ($assignment) use ($submissions) {
                $submission = $submissions->get($assignment->id);
                
                return [
                    'id' => $assignment->id,
                    'title' => $assignment->title,
                    'description' => $assignment->description,
                    'due_date' => $assignment->due_date,
                    'max_score' => $assignment->max_score,
                    'attachment_url' => $assignment->attachment_path 
                        ? Storage::url($assignment->attachment_path) 
                        : null,
                    'is_overdue' => $assignment->due_date && now()->isAfter($assignment->due_date),
                    'days_until_due' => $assignment->due_date 
                        ? now()->diffInDays($assignment->due_date, false) 
                        : null,
                    'user_submission' => $submission ? [
                        'id' => $submission->id,
                        'file_url' => Storage::url($submission->file_url),
                        'grade' => $submission->grade,
                        'submitted_at' => $submission->submitted_at,
                        'is_graded' => $submission->grade !== null,
                    ] : null,
                ];
            }),
        ]);
    }

    /**
     * Get a specific assignment
     */
    public function show(int $courseId, int $unitId, int $lessonId, int $id): JsonResponse
    {
        $assignment = Assignment::with(['course', 'unit', 'lesson'])
            ->where('id', $id)
            ->where('course_id', $courseId)
            ->where('unit_id', $unitId)
            ->where('lesson_id', $lessonId)
            ->where('published', true)
            ->firstOrFail();

        $userId = auth()->id();
        $submission = Submission::where('assignment_id', $id)
            ->where('user_id', $userId)
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $assignment->id,
                'title' => $assignment->title,
                'description' => $assignment->description,
                'due_date' => $assignment->due_date,
                'max_score' => $assignment->max_score,
                'attachment_url' => $assignment->attachment_path 
                    ? Storage::url($assignment->attachment_path) 
                    : null,
                'course' => $assignment->course,
                'unit' => $assignment->unit,
                'lesson' => $assignment->lesson,
                'is_overdue' => $assignment->due_date && now()->isAfter($assignment->due_date),
                'days_until_due' => $assignment->due_date 
                    ? now()->diffInDays($assignment->due_date, false) 
                    : null,
                'user_submission' => $submission ? [
                    'id' => $submission->id,
                    'file_url' => Storage::url($submission->file_url),
                    'grade' => $submission->grade,
                    'submitted_at' => $submission->submitted_at,
                    'is_graded' => $submission->grade !== null,
                ] : null,
            ],
        ]);
    }

    /**
     * Submit an assignment
     */
    public function submit(Request $request, int $courseId, int $unitId, int $lessonId, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:10240', // 10MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $assignment = Assignment::where('id', $id)
            ->where('course_id', $courseId)
            ->where('unit_id', $unitId)
            ->where('lesson_id', $lessonId)
            ->where('published', true)
            ->firstOrFail();

        $userId = auth()->id();

        // Check if already submitted
        $existingSubmission = Submission::where('assignment_id', $id)
            ->where('user_id', $userId)
            ->first();

        if ($existingSubmission) {
            return response()->json([
                'success' => false,
                'message' => 'You have already submitted this assignment.',
            ], 422);
        }

        // Upload file
        $file = $request->file('file');
        $path = $file->store('submissions/' . $id, 'public');

        // Create submission
        $submission = Submission::create([
            'assignment_id' => $id,
            'user_id' => $userId,
            'file_url' => $path,
            'submitted_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Assignment submitted successfully.',
            'data' => [
                'id' => $submission->id,
                'file_url' => Storage::url($submission->file_url),
                'submitted_at' => $submission->submitted_at,
            ],
        ], 201);
    }

    /**
     * Update submission (resubmit)
     */
    public function resubmit(Request $request, int $courseId, int $unitId, int $lessonId, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $assignment = Assignment::where('id', $id)
            ->where('course_id', $courseId)
            ->where('unit_id', $unitId)
            ->where('lesson_id', $lessonId)
            ->where('published', true)
            ->firstOrFail();

        $userId = auth()->id();
        $submission = Submission::where('assignment_id', $id)
            ->where('user_id', $userId)
            ->firstOrFail();

        // Delete old file
        if (Storage::disk('public')->exists($submission->file_url)) {
            Storage::disk('public')->delete($submission->file_url);
        }

        // Upload new file
        $file = $request->file('file');
        $path = $file->store('submissions/' . $id, 'public');

        // Update submission
        $submission->update([
            'file_url' => $path,
            'submitted_at' => now(),
            'grade' => null, // Reset grade on resubmission
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Assignment resubmitted successfully.',
            'data' => [
                'id' => $submission->id,
                'file_url' => Storage::url($submission->file_url),
                'submitted_at' => $submission->submitted_at,
            ],
        ]);
    }
}