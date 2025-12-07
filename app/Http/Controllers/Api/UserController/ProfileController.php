<?php

namespace App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Exception;

class ProfileController extends Controller
{
    /**
     * Success response helper
     */
    protected function success($data, $message = 'Success', $status = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $status);
    }

    /**
     * Error response helper
     */
    protected function error($message, $status = 400, $errors = null)
    {
        $response = [
            'success' => false,
            'message' => $message
        ];

        if ($errors) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $status);
    }

    /**
     * Get authenticated user profile
     * GET /api/profile
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request)
    {
        try {
            Log::info('Profile show request received', [
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'route_name' => $request->route() ? $request->route()->getName() : 'N/A',
                'route_action' => $request->route() ? $request->route()->getActionName() : 'N/A',
            ]);

            if (!Auth::check()) {
                Log::warning('Profile show failed: User not authenticated');
                return $this->error('User not authenticated', 401);
            }

            $user = Auth::user();
            Log::info('Profile retrieved', ['user_id' => $user->id, 'email' => $user->email]);

            return $this->success([
                'id' => $user->id,
                'name' => $user->name,
                'first_name' => $user->first_name,
                'second_name' => $user->second_name,
                'bio' => $user->bio,
                'avatar' => $user->avatar ? Storage::url($user->avatar) : null,
                'email' => $user->email,
                'phone' => $user->phone,
                'district' => $user->district,
                'city' => $user->city,
                'country' => $user->country,
                'created_at' => $user->created_at,
            ], 'Profile retrieved successfully');

        } catch (Exception $e) {
            Log::error('Error fetching profile: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'exception_class' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->error('Failed to retrieve profile', 500);
        }
    }

    /**
     * Update authenticated user profile
     * POST /api/profile (with _method=PATCH for file uploads)
     * PATCH /api/profile (for JSON only)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        try {
            if (!Auth::check()) {
                return $this->error('User not authenticated', 401);
            }

            $user = Auth::user();

            // Log request for debugging
            Log::info('Profile update request', [
                'user_id' => $user->id,
                'method' => $request->method(),
                'content_type' => $request->header('Content-Type'),
                'has_file' => $request->hasFile('avatar'),
                'data' => $request->except(['avatar'])
            ]);

            // Validation rules
            $validator = Validator::make($request->all(), [
                'first_name' => 'sometimes|string|max:255',
                'second_name' => 'sometimes|string|max:255',
                'bio' => 'sometimes|nullable|string|max:1000',
                'avatar' => 'sometimes|nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'city' => 'sometimes|string|nullable|max:255',
                'district' => 'sometimes|string|nullable|max:255',
                'country' => 'sometimes|string|nullable|max:255',
                'phone' => 'sometimes|string|nullable|max:20|regex:/^([0-9\s\-\+\(\)]*)$/',
            ]);

            if ($validator->fails()) {
                return $this->error('Validation failed', 422, $validator->errors());
            }

            $validated = $validator->validated();

            // Handle avatar upload
            if ($request->hasFile('avatar')) {
                // Delete old avatar if exists
                if ($user->avatar && Storage::exists($user->avatar)) {
                    Storage::delete($user->avatar);
                }

                // Store new avatar
                $avatarPath = $request->file('avatar')->store('avatars', 'public');
                $validated['avatar'] = $avatarPath;
            }

            // Update user
            $user->update($validated);
            $user->refresh();

            return $this->success([
                'id' => $user->id,
                'name' => $user->name,
                'first_name' => $user->first_name,
                'second_name' => $user->second_name,
                'bio' => $user->bio,
                'avatar' => $user->avatar ? Storage::url($user->avatar) : null,
                'email' => $user->email,
                'phone' => $user->phone,
                'district' => $user->district,
                'city' => $user->city,
                'country' => $user->country,
                'updated_at' => $user->updated_at,
            ], 'Profile updated successfully');

        } catch (ValidationException $e) {
            return $this->error('Validation failed', 422, $e->errors());
        } catch (Exception $e) {
            Log::error('Error updating profile: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->error('Failed to update profile', 500);
        }
    }

    /**
     * Delete (soft delete) authenticated user account
     * DELETE /api/profile
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy()
    {
        try {
            if (!Auth::check()) {
                return $this->error('User not authenticated', 401);
            }

            $user = Auth::user();

            // Log the account deletion
            Log::info('User account deleted', [
                'user_id' => $user->id,
                'email' => $user->email,
                'deleted_at' => now()
            ]);

            // Soft delete the user (if using SoftDeletes trait)
            $user->delete();

            // Optionally revoke all tokens (if using Sanctum)
            if (method_exists($user, 'tokens')) {
                $user->tokens()->delete();
            }

            return $this->success(null, 'Account deleted successfully');

        } catch (Exception $e) {
            Log::error('Error deleting account: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->error('Failed to delete account', 500);
        }
    }

    /**
     * Update user avatar only
     * POST /api/profile/avatar
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateAvatar(Request $request)
    {
        try {
            if (!Auth::check()) {
                return $this->error('User not authenticated', 401);
            }

            $validator = Validator::make($request->all(), [
                'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            if ($validator->fails()) {
                return $this->error('Validation failed', 422, $validator->errors());
            }

            $user = Auth::user();

            // Delete old avatar if exists
            if ($user->avatar && Storage::exists($user->avatar)) {
                Storage::delete($user->avatar);
            }

            // Store new avatar
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
            $user->update(['avatar' => $avatarPath]);
            $user->refresh();

            return $this->success([
                'avatar' => Storage::url($user->avatar)
            ], 'Avatar updated successfully');

        } catch (ValidationException $e) {
            return $this->error('Validation failed', 422, $e->errors());
        } catch (Exception $e) {
            Log::error('Error updating avatar: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->error('Failed to update avatar', 500);
        }
    }

    /**
     * Remove user avatar
     * DELETE /api/profile/avatar
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeAvatar()
    {
        try {
            if (!Auth::check()) {
                return $this->error('User not authenticated', 401);
            }

            $user = Auth::user();

            // Delete avatar file if exists
            if ($user->avatar && Storage::exists($user->avatar)) {
                Storage::delete($user->avatar);
            }

            // Update user record
            $user->update(['avatar' => null]);
            $user->refresh();

            return $this->success(null, 'Avatar removed successfully');

        } catch (Exception $e) {
            Log::error('Error removing avatar: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->error('Failed to remove avatar', 500);
        }
    }
}