<?php

namespace App\Http\Controllers\Api\CoursePublishRequestController;

use App\Http\Controllers\Controller;
use App\Models\CoursePublishRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class CoursePublishRequestController extends Controller
{
    /**
     * Display a listing of the authenticated user's requests.
     */
    public function index(Request $request)
    {
        $requests = CoursePublishRequest::where('user_id', $request->user()->id)
            ->with('course:id,title')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $requests
        ]);
    }

    /**
     * Store a newly created publish request.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'course_id' => 'required|exists:courses,id',
            'category' => 'nullable|string|max:255',
            'attachment' => 'nullable|file|mimes:pdf,doc,docx,zip|max:10240', // 10MB max
            'uploaded_content' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if user already has a pending request for this course
        $existingRequest = CoursePublishRequest::where('course_id', $request->course_id)
            ->where('user_id', $request->user()->id)
            ->where('status', 'pending')
            ->first();

        if ($existingRequest) {
            return response()->json([
                'success' => false,
                'message' => 'You already have a pending request for this course.'
            ], 409);
        }

        $data = [
            'course_id' => $request->course_id,
            'user_id' => $request->user()->id,
            'category' => $request->category,
            'uploaded_content' => $request->uploaded_content,
            'status' => 'pending'
        ];

        // Handle file upload
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $path = $file->store('course-publish-requests', 'public');
            $data['attachment_path'] = $path;
        }

        $publishRequest = CoursePublishRequest::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Course publish request submitted successfully.',
            'data' => $publishRequest->load('course:id,title')
        ], 201);
    }

    /**
     * Display the specified request.
     */
    public function show(Request $request, $id)
    {
        $publishRequest = CoursePublishRequest::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->with('course:id,title')
            ->first();

        if (!$publishRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Request not found.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $publishRequest
        ]);
    }

    /**
     * Update the specified request (only if status is pending).
     */
    public function update(Request $request, $id)
    {
        $publishRequest = CoursePublishRequest::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$publishRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Request not found.'
            ], 404);
        }

        if ($publishRequest->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot update a request that has been approved or rejected.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'category' => 'nullable|string|max:255',
            'attachment' => 'nullable|file|mimes:pdf,doc,docx,zip|max:10240',
            'uploaded_content' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->only(['category', 'uploaded_content']);

        // Handle new file upload
        if ($request->hasFile('attachment')) {
            // Delete old file if exists
            if ($publishRequest->attachment_path) {
                Storage::disk('public')->delete($publishRequest->attachment_path);
            }

            $file = $request->file('attachment');
            $path = $file->store('course-publish-requests', 'public');
            $data['attachment_path'] = $path;
        }

        $publishRequest->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Request updated successfully.',
            'data' => $publishRequest->load('course:id,title')
        ]);
    }

    /**
     * Remove the specified request (only if status is pending).
     */
    public function destroy(Request $request, $id)
    {
        $publishRequest = CoursePublishRequest::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$publishRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Request not found.'
            ], 404);
        }

        if ($publishRequest->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete a request that has been approved or rejected.'
            ], 403);
        }

        // Delete attachment file if exists
        if ($publishRequest->attachment_path) {
            Storage::disk('public')->delete($publishRequest->attachment_path);
        }

        $publishRequest->delete();

        return response()->json([
            'success' => true,
            'message' => 'Request deleted successfully.'
        ]);
    }

    /**
     * Get statistics for user's requests.
     */
    public function statistics(Request $request)
    {
        $userId = $request->user()->id;

        $stats = [
            'total' => CoursePublishRequest::where('user_id', $userId)->count(),
            'pending' => CoursePublishRequest::where('user_id', $userId)->where('status', 'pending')->count(),
            'approved' => CoursePublishRequest::where('user_id', $userId)->where('status', 'approved')->count(),
            'rejected' => CoursePublishRequest::where('user_id', $userId)->where('status', 'rejected')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}