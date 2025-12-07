<?php

namespace App\Http\Controllers\Api\UserController;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    public function show()
    {
        return response()->json([
            'success' => true,
            'message' => 'Profile retrieved successfully.',
            'data' => Auth::user(),
        ]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'first_name' => 'sometimes|string|max:255',
            'second_name' => 'sometimes|string|max:255',
            'bio' => 'sometimes|nullable|string',
            'avatar' => 'sometimes|nullable|string',
            'city' => 'sometimes|string|nullable',
            'district' => 'sometimes|string|nullable',
            'country' => 'sometimes|string|nullable',
            'phone' => 'sometimes|string|nullable',
        ]);

        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully.',
            'data' => $user->fresh(),
        ]);
    }

    public function destroy()
    {
        $user = Auth::user();
        $user->delete(); // Soft delete

        return response()->json([
            'success' => true,
            'message' => 'Account deleted successfully.',
        ]);
    }
}
