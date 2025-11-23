<?php


namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Models\User;
use App\Mail\EmailVerificationMail;
use App\Mail\PasswordResetMail;
use Spatie\Permission\Models\Role;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Auth\Events\PasswordReset;

class AuthController extends Controller
{
    /**
     * Register a new user
     */
    public function register(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'first_name' => [
                    'required',
                    'string',
                    'min:2',
                    'max:100',
                    'regex:/^[a-zA-Z\s\-\']+$/',
                ],
                'second_name' => [
                    'required',
                    'string',
                    'min:2',
                    'max:100',
                    'regex:/^[a-zA-Z\s\-\']+$/',
                ],
                'email' => [
                    'required',
                    'string',
                    'email:rfc,dns',
                    'max:255',
                    'unique:users,email',
                    'regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
                ],
                'phone' => [
                    'nullable',
                    'string',
                    'min:10',
                    'max:20',
                    'regex:/^[0-9\+\-\(\)\s]+$/',
                ],
                'password' => [
                    'required',
                    'string',
                    'confirmed',
                    PasswordRule::min(8)
                        ->mixedCase()
                        ->letters()
                        ->numbers()
                        ->symbols()
                        ->uncompromised(),
                ],
                'password_confirmation' => 'required|string',
                'role' => [
                    'required',
                    'string',
                    'in:learner,instructor,admin',
                ],
            ], [
                'first_name.required' => 'First name is required.',
                'first_name.min' => 'First name must be at least 2 characters.',
                'first_name.regex' => 'First name can only contain letters, spaces, hyphens, and apostrophes.',
                'second_name.required' => 'Last name is required.',
                'second_name.min' => 'Last name must be at least 2 characters.',
                'second_name.regex' => 'Last name can only contain letters, spaces, hyphens, and apostrophes.',
                'email.required' => 'Email address is required.',
                'email.email' => 'Please provide a valid email address.',
                'email.unique' => 'This email address is already registered.',
                'email.regex' => 'Email format is invalid.',
                'phone.min' => 'Phone number must be at least 10 characters.',
                'phone.regex' => 'Phone number format is invalid.',
                'password.required' => 'Password is required.',
                'password.confirmed' => 'Password confirmation does not match.',
                'password_confirmation.required' => 'Password confirmation is required.',
                'role.required' => 'User role is required.',
                'role.in' => 'Role must be one of: learner, instructor, or admin.',
            ]);

            if ($validator->fails()) {
                Log::warning('Registration validation failed', [
                    'email' => $request->input('email'),
                    'errors' => $validator->errors()->toArray(),
                    'ip' => $request->ip(),
                ]);

                return response()->json([
                    'message' => 'Validation failed. Please check your input.',
                    'errors' => $validator->errors(),
                    'failed_fields' => array_keys($validator->errors()->toArray()),
                ], 422);
            }

            $validated = $validator->validated();

            // Check if role exists
            if (!Role::where('name', $validated['role'])->exists()) {
                Log::error('Role does not exist in database', [
                    'role' => $validated['role'],
                    'available_roles' => Role::pluck('name')->toArray(),
                ]);

                return response()->json([
                    'message' => 'Invalid role provided.',
                    'error' => 'Role does not exist in the system.',
                    'available_roles' => Role::pluck('name')->toArray(),
                ], 422);
            }

            // Check blocked email domains
            $blockedDomains = ['tempmail.com', 'throwaway.email'];
            $emailDomain = substr(strrchr($validated['email'], "@"), 1);
            if (in_array($emailDomain, $blockedDomains)) {
                Log::warning('Blocked email domain attempted registration', [
                    'email' => $validated['email'],
                    'domain' => $emailDomain,
                    'ip' => $request->ip(),
                ]);

                return response()->json([
                    'message' => 'Registration failed.',
                    'error' => 'This email domain is not allowed.',
                ], 422);
            }

            Log::info('Attempting to create user', [
                'email' => strtolower(trim($validated['email'])),
            ]);

            // Create user
            $user = User::create([
                'first_name' => trim($validated['first_name']),
                'second_name' => trim($validated['second_name']),
                'email' => strtolower(trim($validated['email'])),
                'phone' => $validated['phone'] ?? null,
                'password' => Hash::make($validated['password']),
                'email_verified_at' => null, // Not verified yet
            ]);

            if (!$user) {
                Log::error('User creation failed', [
                    'email' => $validated['email'],
                ]);

                return response()->json([
                    'message' => 'Failed to create user account.',
                    'error' => 'User object was not created.',
                ], 500);
            }

            // Assign role
            try {
                $user->assignRole($validated['role']);
            } catch (Exception $e) {
                Log::error('Role assignment failed', [
                    'user_id' => $user->id,
                    'role' => $validated['role'],
                    'error' => $e->getMessage(),
                ]);

                $user->delete();

                return response()->json([
                    'message' => 'Failed to assign user role.',
                    'error' => 'Role assignment error.',
                ], 500);
            }

            // Send verification email
            try {
                $verificationToken = Str::random(64);
                $user->update(['remember_token' => $verificationToken]);

                Mail::to($user->email)->send(new EmailVerificationMail($user, $verificationToken));

                Log::info('Verification email sent', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                ]);
            } catch (Exception $e) {
                Log::error('Failed to send verification email', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
                // Continue registration even if email fails
            }

            // Create token
            $token = $user->createToken('api_token')->plainTextToken;

            Log::info('User registered successfully', [
                'user_id' => $user->id,
                'email' => $user->email,
                'role' => $validated['role'],
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'message' => 'User registered successfully. Please check your email to verify your account.',
                'user' => $user,
                'role' => $validated['role'],
                'token' => $token,
                'email_sent' => true,
            ], 201);

        } catch (QueryException $e) {
            Log::error('Database error during registration', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'ip' => $request->ip(),
            ]);

            if ($e->getCode() === '23000') {
                return response()->json([
                    'message' => 'Duplicate entry detected.',
                    'error' => 'This email or phone may already exist.',
                ], 422);
            }

            return response()->json([
                'message' => 'A database error occurred.',
                'error' => 'Please try again later or contact support.',
            ], 500);

        } catch (Exception $e) {
            Log::error('Unexpected error during registration', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'message' => 'An unexpected error occurred.',
                'error' => 'Please try again later or contact support.',
            ], 500);
        }
    }

    /**
     * Verify email
     */
    public function verifyEmail(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'token' => 'required|string',
                'email' => 'required|email|exists:users,email',
            ], [
                'token.required' => 'Verification token is required.',
                'email.required' => 'Email is required.',
                'email.exists' => 'User not found.',
            ]);

            if ($validator->fails()) {
                Log::warning('Email verification validation failed', [
                    'errors' => $validator->errors()->toArray(),
                    'ip' => $request->ip(),
                ]);

                return response()->json([
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $validated = $validator->validated();
            $user = User::where('email', $validated['email'])->first();

            if (!$user) {
                return response()->json([
                    'message' => 'User not found.',
                ], 404);
            }

            if ($user->email_verified_at) {
                return response()->json([
                    'message' => 'Email already verified.',
                ], 200);
            }

            if ($user->remember_token !== $validated['token']) {
                Log::warning('Invalid verification token', [
                    'user_id' => $user->id,
                    'ip' => $request->ip(),
                ]);

                return response()->json([
                    'message' => 'Invalid verification token.',
                ], 400);
            }

            $user->update([
                'email_verified_at' => now(),
                'remember_token' => null,
            ]);

            Log::info('Email verified successfully', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            return response()->json([
                'message' => 'Email verified successfully.',
                'user' => $user,
            ], 200);

        } catch (Exception $e) {
            Log::error('Error during email verification', [
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'message' => 'An error occurred during verification.',
                'error' => 'Please try again.',
            ], 500);
        }
    }

    /**
     * Resend verification email
     */
    public function resendVerificationEmail(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|exists:users,email',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $user = User::where('email', $request->email)->first();

            if ($user->email_verified_at) {
                return response()->json([
                    'message' => 'Email already verified.',
                ], 200);
            }

            $verificationToken = Str::random(64);
            $user->update(['remember_token' => $verificationToken]);

            Mail::to($user->email)->send(new EmailVerificationMail($user, $verificationToken));

            Log::info('Verification email resent', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            return response()->json([
                'message' => 'Verification email sent successfully.',
            ], 200);

        } catch (Exception $e) {
            Log::error('Error resending verification email', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to send verification email.',
                'error' => 'Please try again.',
            ], 500);
        }
    }

    /**
     * Login a user
     */
    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => [
                    'required',
                    'string',
                    'email:rfc',
                    'max:255',
                ],
                'password' => [
                    'required',
                    'string',
                    'min:6',
                ],
            ], [
                'email.required' => 'Email address is required.',
                'email.email' => 'Please provide a valid email address.',
                'password.required' => 'Password is required.',
                'password.min' => 'Password must be at least 6 characters.',
            ]);

            if ($validator->fails()) {
                Log::warning('Login validation failed', [
                    'email' => $request->input('email'),
                    'errors' => $validator->errors()->toArray(),
                    'ip' => $request->ip(),
                ]);

                return response()->json([
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $validated = $validator->validated();
            $email = strtolower(trim($validated['email']));

            $user = User::where('email', $email)->first();

            if (!$user) {
                Log::warning('Login attempt with non-existent email', [
                    'email' => $email,
                    'ip' => $request->ip(),
                ]);

                return response()->json([
                    'message' => 'Invalid credentials.',
                    'error' => 'Email or password is incorrect.',
                ], 401);
            }

            if (!Hash::check($validated['password'], $user->password)) {
                Log::warning('Login attempt with incorrect password', [
                    'user_id' => $user->id,
                    'email' => $email,
                    'ip' => $request->ip(),
                ]);

                return response()->json([
                    'message' => 'Invalid credentials.',
                    'error' => 'Email or password is incorrect.',
                ], 401);
            }

            // Check if email is verified (optional - remove if you don't want to enforce)
            if (!$user->email_verified_at) {
                Log::warning('Login attempt with unverified email', [
                    'user_id' => $user->id,
                    'email' => $email,
                ]);

                return response()->json([
                    'message' => 'Email not verified.',
                    'error' => 'Please verify your email before logging in.',
                    'email_verified' => false,
                ], 403);
            }

            $token = $user->createToken('api_token')->plainTextToken;
            $user->load('roles');

            Log::info('User logged in successfully', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'message' => 'Login successful.',
                'user' => $user,
                'roles' => $user->getRoleNames(),
                'token' => $token,
            ], 200);

        } catch (Exception $e) {
            Log::error('Unexpected error during login', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'message' => 'An unexpected error occurred.',
                'error' => 'Please try again later or contact support.',
            ], 500);
        }
    }

    /**
     * Forgot password - send reset link
     */
    public function forgotPassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|exists:users,email',
            ], [
                'email.required' => 'Email is required.',
                'email.exists' => 'We could not find a user with this email address.',
            ]);

            if ($validator->fails()) {
                Log::warning('Forgot password validation failed', [
                    'email' => $request->input('email'),
                    'errors' => $validator->errors()->toArray(),
                    'ip' => $request->ip(),
                ]);

                return response()->json([
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $user = User::where('email', $request->email)->first();

            // Generate reset token
            $resetToken = Str::random(64);
            
            // Store in password_reset_tokens table
            \DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $user->email],
                [
                    'email' => $user->email,
                    'token' => Hash::make($resetToken),
                    'created_at' => now(),
                ]
            );

            // Send reset email
            try {
                Mail::to($user->email)->send(new PasswordResetMail($user, $resetToken));

                Log::info('Password reset email sent', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                ]);

                return response()->json([
                    'message' => 'Password reset link sent to your email.',
                ], 200);

            } catch (Exception $e) {
                Log::error('Failed to send password reset email', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);

                return response()->json([
                    'message' => 'Failed to send password reset email.',
                    'error' => 'Please try again later.',
                ], 500);
            }

        } catch (Exception $e) {
            Log::error('Error in forgot password', [
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'message' => 'An error occurred.',
                'error' => 'Please try again later.',
            ], 500);
        }
    }

    /**
     * Reset password
     */
    public function resetPassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'token' => 'required|string',
                'email' => 'required|email|exists:users,email',
                'password' => [
                    'required',
                    'string',
                    'confirmed',
                    PasswordRule::min(8)
                        ->mixedCase()
                        ->letters()
                        ->numbers()
                        ->symbols(),
                ],
            ], [
                'token.required' => 'Reset token is required.',
                'email.exists' => 'User not found.',
                'password.confirmed' => 'Password confirmation does not match.',
            ]);

            if ($validator->fails()) {
                Log::warning('Reset password validation failed', [
                    'errors' => $validator->errors()->toArray(),
                    'ip' => $request->ip(),
                ]);

                return response()->json([
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $validated = $validator->validated();

            // Check if token exists and is valid
            $resetRecord = \DB::table('password_reset_tokens')
                ->where('email', $validated['email'])
                ->first();

            if (!$resetRecord) {
                Log::warning('Password reset attempted with non-existent token', [
                    'email' => $validated['email'],
                ]);

                return response()->json([
                    'message' => 'Invalid or expired reset token.',
                ], 400);
            }

            // Check if token matches
            if (!Hash::check($validated['token'], $resetRecord->token)) {
                Log::warning('Password reset attempted with invalid token', [
                    'email' => $validated['email'],
                ]);

                return response()->json([
                    'message' => 'Invalid reset token.',
                ], 400);
            }

            // Check if token is expired (24 hours)
            if (now()->diffInHours($resetRecord->created_at) > 24) {
                Log::warning('Password reset attempted with expired token', [
                    'email' => $validated['email'],
                ]);

                \DB::table('password_reset_tokens')
                    ->where('email', $validated['email'])
                    ->delete();

                return response()->json([
                    'message' => 'Reset token has expired. Please request a new one.',
                ], 400);
            }

            // Update password
            $user = User::where('email', $validated['email'])->first();
            $user->update([
                'password' => Hash::make($validated['password']),
            ]);

            // Delete reset token
            \DB::table('password_reset_tokens')
                ->where('email', $validated['email'])
                ->delete();

            // Revoke all tokens for security
            $user->tokens()->delete();

            Log::info('Password reset successful', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            return response()->json([
                'message' => 'Password has been reset successfully. Please login with your new password.',
            ], 200);

        } catch (Exception $e) {
            Log::error('Error during password reset', [
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'message' => 'Failed to reset password.',
                'error' => 'Please try again later.',
            ], 500);
        }
    }

    /**
     * Change password (authenticated user)
     */
    public function changePassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'current_password' => 'required|string',
                'password' => [
                    'required',
                    'string',
                    'confirmed',
                    'different:current_password',
                    PasswordRule::min(8)
                        ->mixedCase()
                        ->letters()
                        ->numbers()
                        ->symbols(),
                ],
            ], [
                'current_password.required' => 'Current password is required.',
                'password.different' => 'New password must be different from current password.',
                'password.confirmed' => 'Password confirmation does not match.',
            ]);

            if ($validator->fails()) {
                Log::warning('Change password validation failed', [
                    'user_id' => $request->user()->id,
                    'errors' => $validator->errors()->toArray(),
                ]);

                return response()->json([
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $user = $request->user();

            if (!Hash::check($request->current_password, $user->password)) {
                Log::warning('Change password failed - incorrect current password', [
                    'user_id' => $user->id,
                ]);

                return response()->json([
                    'message' => 'Current password is incorrect.',
                ], 400);
            }

            $user->update([
                'password' => Hash::make($request->password),
            ]);

            // Revoke all tokens for security
            $user->tokens()->delete();

            Log::info('Password changed successfully', [
                'user_id' => $user->id,
            ]);

            return response()->json([
                'message' => 'Password changed successfully. Please login again with your new password.',
            ], 200);

        } catch (Exception $e) {
            Log::error('Error changing password', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to change password.',
                'error' => 'Please try again later.',
            ], 500);
        }
    }

    /**
     * Logout current user
     */
    public function logout(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                Log::warning('Logout attempt without authentication', [
                    'ip' => $request->ip(),
                ]);

                return response()->json([
                    'message' => 'User not authenticated.',
                    'error' => 'No active session found.',
                ], 401);
            }

            $userId = $user->id;
            $user->currentAccessToken()->delete();

            Log::info('User logged out successfully', [
                'user_id' => $userId,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'message' => 'Logged out successfully.'
            ], 200);

        } catch (Exception $e) {
            Log::error('Error during logout', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'message' => 'An unexpected error occurred during logout.',
                'error' => 'Please try again.',
            ], 500);
        }
    }

    /**
     * Get current user profile
     */
    public function profile(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                Log::warning('Profile access attempt without authentication', [
                    'ip' => $request->ip(),
                ]);

                return response()->json([
                    'message' => 'User not authenticated.',
                    'error' => 'Please login to access your profile.',
                ], 401);
            }

            $user->load('roles');

            Log::info('User profile accessed', [
                'user_id' => $user->id,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'user' => $user,
                'roles' => $user->getRoleNames(),
            ], 200);

        } catch (Exception $e) {
            Log::error('Error fetching user profile', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'message' => 'Failed to fetch user profile.',
                'error' => 'Please try again later or contact support.',
            ], 500);
        }
    }
}