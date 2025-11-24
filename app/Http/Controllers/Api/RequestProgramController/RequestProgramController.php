<?php

namespace App\Http\Controllers\Api\RequestProgramController;

use App\Http\Controllers\Controller;
use App\Models\RequestProgram;
use Illuminate\Http\Request;

class RequestProgramController extends Controller
{
    /**
     * Get all requests for authenticated user
     */
    public function index()
    {
        $requests = RequestProgram::where('user_id', auth()->id())
            ->with('user')
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $requests
        ]);
    }

    /**
     * Create new program request
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'requested_by' => 'required|string|max:255',
            'program_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'requested_date' => 'required|date',
            'requested_features' => 'nullable|string',
        ]);

        $validated['user_id'] = auth()->id();
        $validated['status'] = 'pending';

        $requestProgram = RequestProgram::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Program request submitted successfully',
            'data' => $requestProgram->load('user')
        ], 201);
    }
}