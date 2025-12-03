<?php

namespace App\Http\Controllers\Api\CaseStudyController\Instructor;

use App\Http\Controllers\Controller;
use App\Models\CaseStudy;
use App\Models\CaseStudyAnswer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CaseStudyController extends Controller
{
    /**
     * Display a listing of instructor's case studies
     */
    public function index(Request $request)
    {
        $caseStudies = CaseStudy::where('instructor_id', $request->user()->id)
            ->with(['answers' => function($query) {
                $query->withCount('files');
            }])
            ->withCount('answers')
            ->latest()
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $caseStudies
        ]);
    }

    /**
     * Store a newly created case study
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'duration' => 'required|integer|min:1',
            'status' => 'sometimes|in:open,closed'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $caseStudy = CaseStudy::create([
            'title' => $request->title,
            'content' => $request->content,
            'duration' => $request->duration,
            'status' => $request->status ?? 'open',
            'instructor_id' => $request->user()->id
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Case study created successfully',
            'data' => $caseStudy
        ], 201);
    }

    /**
     * Display the specified case study
     */
    public function show(Request $request, $id)
    {
        $caseStudy = CaseStudy::where('instructor_id', $request->user()->id)
            ->with(['answers.learner', 'answers.files'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $caseStudy
        ]);
    }

    /**
     * Update the specified case study
     */
    public function update(Request $request, $id)
    {
        $caseStudy = CaseStudy::where('instructor_id', $request->user()->id)
            ->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
            'duration' => 'sometimes|integer|min:1',
            'status' => 'sometimes|in:open,closed'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $caseStudy->update($request->only(['title', 'content', 'duration', 'status']));

        return response()->json([
            'success' => true,
            'message' => 'Case study updated successfully',
            'data' => $caseStudy
        ]);
    }

    /**
     * Remove the specified case study
     */
    public function destroy(Request $request, $id)
    {
        $caseStudy = CaseStudy::where('instructor_id', $request->user()->id)
            ->findOrFail($id);

        $caseStudy->delete();

        return response()->json([
            'success' => true,
            'message' => 'Case study deleted successfully'
        ]);
    }

    /**
     * Get all answers for a specific case study
     */
    public function answers(Request $request, $id)
    {
        $caseStudy = CaseStudy::where('instructor_id', $request->user()->id)
            ->findOrFail($id);

        $answers = CaseStudyAnswer::where('case_study_id', $id)
            ->with(['learner', 'files'])
            ->latest('submitted_at')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $answers
        ]);
    }

    /**
     * View a specific answer
     */
    public function viewAnswer(Request $request, $caseStudyId, $answerId)
    {
        $caseStudy = CaseStudy::where('instructor_id', $request->user()->id)
            ->findOrFail($caseStudyId);

        $answer = CaseStudyAnswer::where('case_study_id', $caseStudyId)
            ->with(['learner', 'files'])
            ->findOrFail($answerId);

        return response()->json([
            'success' => true,
            'data' => $answer
        ]);
    }

    /**
     * Toggle case study status (open/closed)
     */
    public function toggleStatus(Request $request, $id)
    {
        $caseStudy = CaseStudy::where('instructor_id', $request->user()->id)
            ->findOrFail($id);

        $caseStudy->status = $caseStudy->status === 'open' ? 'closed' : 'open';
        $caseStudy->save();

        return response()->json([
            'success' => true,
            'message' => 'Case study status updated',
            'data' => $caseStudy
        ]);
    }
}