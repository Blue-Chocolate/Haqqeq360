<?php

namespace App\Http\Controllers\Api\ReportController;

use App\Http\Controllers\Controller;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;

class ReportController extends Controller
{
    /**
     * Get all reports for authenticated user
     */
    public function index(Request $request)
    {
        try {
            $query = Report::where('user_id', Auth::id());

            // Filter by enrollable_type if provided
            if ($request->has('enrollable_type')) {
                $query->where('enrollable_type', 'like', '%' . $request->enrollable_type . '%');
            }

            // Sort by completion_rate or grade_avg
            if ($request->has('sort_by')) {
                $sortBy = $request->sort_by; // completion_rate or grade_avg
                $sortOrder = $request->get('sort_order', 'desc'); // desc or asc
                $query->orderBy($sortBy, $sortOrder);
            } else {
                $query->latest(); // Default: newest first
            }

            $reports = $query->paginate($request->get('per_page', 15));

            return response()->json([
                'status' => 'success',
                'message' => 'Reports retrieved successfully',
                'data' => $reports
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve reports: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific report by ID
     */
    public function show($id)
    {
        try {
            $report = Report::where('user_id', Auth::id())
                ->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'message' => 'Report retrieved successfully',
                'data' => $report
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Report not found'
            ], 404);
        }
    }

    /**
     * Get reports for a specific enrollable (course, bootcamp, etc.)
     */
    public function getByEnrollable(Request $request)
    {
        try {
            $validated = $request->validate([
                'enrollable_type' => 'required|string|in:course,bootcamp,workshop,program',
                'enrollable_id' => 'required|integer'
            ]);

            // Map short type to full class name
            $modelMap = [
                'course' => 'App\Models\Course',
                'bootcamp' => 'App\Models\Bootcamp',
                'workshop' => 'App\Models\Workshop',
                'program' => 'App\Models\Program',
            ];

            $enrollableType = $modelMap[$validated['enrollable_type']];

            $report = Report::where('user_id', Auth::id())
                ->where('enrollable_type', $enrollableType)
                ->where('enrollable_id', $validated['enrollable_id'])
                ->first();

            if (!$report) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No report found for this enrollment'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Report retrieved successfully',
                'data' => $report
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve report: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get summary statistics of all user reports
     */
    public function summary()
    {
        try {
            $userId = Auth::id();

            $summary = [
                'total_reports' => Report::where('user_id', $userId)->count(),
                'average_completion' => Report::where('user_id', $userId)->avg('completion_rate'),
                'average_grade' => Report::where('user_id', $userId)->avg('grade_avg'),
                'completed_count' => Report::where('user_id', $userId)
                    ->where('completion_rate', 100)
                    ->count(),
                'by_type' => Report::where('user_id', $userId)
                    ->selectRaw('enrollable_type, COUNT(*) as count, AVG(completion_rate) as avg_completion, AVG(grade_avg) as avg_grade')
                    ->groupBy('enrollable_type')
                    ->get()
            ];

            return response()->json([
                'status' => 'success',
                'message' => 'Report summary retrieved successfully',
                'data' => $summary
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve summary: ' . $e->getMessage()
            ], 500);
        }
    }
}