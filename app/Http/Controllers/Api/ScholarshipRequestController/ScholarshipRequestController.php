<?php

namespace App\Http\Controllers\Api\ScholarshipRequestController;

use App\Http\Controllers\Controller;
use App\Repositories\ScholarshipRequest\ScholarshipRequestRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

class ScholarshipRequestController extends Controller
{
    protected $repo;

    public function __construct(ScholarshipRequestRepository $repo)
    {
        $this->repo = $repo;
    }

    public function index()
    {
        try {
            $requests = $this->repo->getAllByUser(auth()->id());
            return response()->json(['success' => true, 'data' => $requests]);
        } catch (Exception $e) {
            Log::error('Fetch failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'applicant_name' => 'required|string|max:255',
                'number_of_participants' => 'required|integer|min:1',
                'program_type' => 'required|string|max:255',
                'skills_and_needs' => 'nullable|string',
                'attachments' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:2048',
            ]);

            if ($request->hasFile('attachments')) {
                $validated['attachments'] = $request->file('attachments')->store('scholarship_attachments', 'public');
            }

            $validated['user_id'] = auth()->id();
            $validated['status'] = 'pending';

            $data = $this->repo->create($validated);

            return response()->json(['success' => true, 'message' => 'Scholarship request submitted', 'data' => $data], 201);
        } catch (Exception $e) {
            Log::error('Store failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}