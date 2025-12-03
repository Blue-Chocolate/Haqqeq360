<?php

namespace App\Http\Controllers\Api\CaseStudyController\Student;

use App\Http\Controllers\Controller;
use App\Models\CaseStudy;
use App\Models\CaseStudyAnswer;
use App\Models\CaseStudyAnswerFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class CaseStudyController extends Controller
{
    /**
     * Display a listing of available case studies
     */
    public function index(Request $request)
    {
        $caseStudies = CaseStudy::where('status', 'open')
            ->with('instructor:id,name,first_name,second_name')
            ->withExists([
                'answers as has_submitted' => function($query) use ($request) {
                    $query->where('learner_id', $request->user()->id);
                }
            ])
            ->latest()
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $caseStudies
        ]);
    }

    /**
     * Display the specified case study
     */
    public function show(Request $request, $id)
    {
        $caseStudy = CaseStudy::where('status', 'open')
            ->with('instructor:id,name,first_name,second_name')
            ->findOrFail($id);

        // Check if learner has already submitted
        $hasSubmitted = CaseStudyAnswer::where('case_study_id', $id)
            ->where('learner_id', $request->user()->id)
            ->exists();

        $myAnswer = null;
        if ($hasSubmitted) {
            $myAnswer = CaseStudyAnswer::where('case_study_id', $id)
                ->where('learner_id', $request->user()->id)
                ->with('files')
                ->first();
        }

        return response()->json([
            'success' => true,
            'data' => $caseStudy,
            'has_submitted' => $hasSubmitted,
            'my_answer' => $myAnswer
        ]);
    }

    /**
     * Submit an answer to a case study
     */
    public function submitAnswer(Request $request, $id)
    {
        $caseStudy = CaseStudy::where('status', 'open')->findOrFail($id);

        // Check if already submitted
        $existingAnswer = CaseStudyAnswer::where('case_study_id', $id)
            ->where('learner_id', $request->user()->id)
            ->first();

        if ($existingAnswer) {
            return response()->json([
                'success' => false,
                'message' => 'You have already submitted an answer for this case study'
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'answer_text' => 'nullable|string',
            'files.*' => 'nullable|file|mimes:pdf,doc,docx|max:10240' // 10MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // At least one of answer_text or files must be provided
        if (empty($request->answer_text) && !$request->hasFile('files')) {
            return response()->json([
                'success' => false,
                'message' => 'Please provide either an answer text or upload files'
            ], 422);
        }

        // Create answer
        $answer = CaseStudyAnswer::create([
            'case_study_id' => $id,
            'learner_id' => $request->user()->id,
            'answer_text' => $request->answer_text,
            'submitted_at' => now()
        ]);

        // Handle file uploads
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $path = $file->store('case_study_answers', 'public');
                
                CaseStudyAnswerFile::create([
                    'answer_id' => $answer->id,
                    'file_path' => $path,
                    'original_name' => $file->getClientOriginalName()
                ]);
            }
        }

        $answer->load('files');

        return response()->json([
            'success' => true,
            'message' => 'Answer submitted successfully',
            'data' => $answer
        ], 201);
    }

    /**
     * Update an existing answer (if not yet graded or within time limit)
     */
    public function updateAnswer(Request $request, $id)
    {
        $answer = CaseStudyAnswer::where('learner_id', $request->user()->id)
            ->with('caseStudy')
            ->findOrFail($id);

        // Check if case study is still open
        if ($answer->caseStudy->status !== 'open') {
            return response()->json([
                'success' => false,
                'message' => 'This case study is closed and answers cannot be modified'
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'answer_text' => 'nullable|string',
            'files.*' => 'nullable|file|mimes:pdf,doc,docx|max:10240'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $answer->update([
            'answer_text' => $request->answer_text ?? $answer->answer_text,
            'submitted_at' => now()
        ]);

        // Handle new file uploads
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $path = $file->store('case_study_answers', 'public');
                
                CaseStudyAnswerFile::create([
                    'answer_id' => $answer->id,
                    'file_path' => $path,
                    'original_name' => $file->getClientOriginalName()
                ]);
            }
        }

        $answer->load('files');

        return response()->json([
            'success' => true,
            'message' => 'Answer updated successfully',
            'data' => $answer
        ]);
    }

    /**
     * Delete a file from an answer
     */
    public function deleteFile(Request $request, $answerId, $fileId)
    {
        $answer = CaseStudyAnswer::where('learner_id', $request->user()->id)
            ->findOrFail($answerId);

        $file = CaseStudyAnswerFile::where('answer_id', $answerId)
            ->findOrFail($fileId);

        // Delete file from storage
        Storage::disk('public')->delete($file->file_path);

        // Delete record
        $file->delete();

        return response()->json([
            'success' => true,
            'message' => 'File deleted successfully'
        ]);
    }

    /**
     * Get my submissions
     */
    public function mySubmissions(Request $request)
    {
        $submissions = CaseStudyAnswer::where('learner_id', $request->user()->id)
            ->with(['caseStudy', 'files'])
            ->latest('submitted_at')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $submissions
        ]);
    }

    /**
     * Download a file
     */
    public function downloadFile($answerId, $fileId)
    {
        $file = CaseStudyAnswerFile::where('answer_id', $answerId)
            ->findOrFail($fileId);

        return Storage::disk('public')->download($file->file_path, $file->original_name);
    }
}