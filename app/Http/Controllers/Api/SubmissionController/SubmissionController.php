<?php

namespace App\Http\Controllers\Api\SubmissionController;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SubmissionController extends Controller
{
    /**
     * Get all submissions for an assignment
     */
    public function getAssignmentSubmissions(int $assignmentId): JsonResponse
    {
        $assignment = Assignment::findOrFail($assignmentId);

        $submissions = Submission::with('user')
            ->where('assignment_id', $assignmentId)
            ->orderBy('submitted_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'assignment' => [
                    'id' => $assignment->id,
                    'title' => $assignment->title,
                    'max_score' => $assignment->max_score,
                    'due_date' => $assignment->due_date,
                ],
                'statistics' => [
                    'total_submissions' => $submissions->count(),
                    'graded' => $submissions->whereNotNull('grade')->count(),
                    'pending' => $submissions->whereNull('grade')->count(),
                    'average_grade' => $submissions->whereNotNull('grade')->avg('grade'),
                ],
                'submissions' => $submissions->map(function ($submission) use ($assignment) {
                    return [
                        'id' => $submission->id,
                        'user' => [
                            'id' => $submission->user->id,
                            'name' => $submission->user->name,
                            'email' => $submission->user->email,
                        ],
                        'file_url' => Storage::url($submission->file_url),
                        'grade' => $submission->grade,
                        'submitted_at' => $submission->submitted_at,
                        'is_graded' => $submission->grade !== null,
                        'is_overdue' => $assignment->due_date 
                            && $submission->submitted_at->isAfter($assignment->due_date),
                        'created_at' => $submission->created_at,
                        'updated_at' => $submission->updated_at,
                    ];
                }),
            ],
        ]);
    }

    /**
     * Grade a submission
     */
    public function grade(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'grade' => 'required|numeric|min:0',
        ]);

        $submission = Submission::with('assignment')->findOrFail($id);

        // Validate grade doesn't exceed max score
        if ($request->grade > $submission->assignment->max_score) {
            return response()->json([
                'success' => false,
                'message' => "Grade cannot exceed maximum score of {$submission->assignment->max_score}.",
            ], 422);
        }

        $submission->update([
            'grade' => $request->grade,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Submission graded successfully.',
            'data' => [
                'id' => $submission->id,
                'grade' => $submission->grade,
                'updated_at' => $submission->updated_at,
            ],
        ]);
    }
}