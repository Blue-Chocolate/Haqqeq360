<?php
namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Log;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;
use Exception;

class AuthService
{
    /**
     * Register a new user
     */
    public function register(array $data): array
    {
        try {
            // Create user
            $user = User::create([
                'first_name' => trim($data['first_name']),
                'second_name' => trim($data['second_name']),
                'email' => strtolower(trim($data['email'])),
                'phone' => $data['phone'] ?? null,
                'password' => Hash::make($data['password']),
            ]);

            // Assign role
            if (isset($data['role'])) {
                $user->assignRole($data['role']);
            }

            // Create token
            $token = $user->createToken('api_token')->plainTextToken;

            Log::info('User registered successfully', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            return [
                'success' => true,
                'user' => $user->load('roles'),
                'token' => $token,
                'message' => 'User registered successfully.',
            ];

        } catch (Exception $e) {
            Log::error('User registration failed', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);

            throw $e;
        }
    }

    /**
     * Login user
     */
    public function login(array $credentials): array
    {
        $email = strtolower(trim($credentials['email']));
        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            Log::warning('Login attempt failed', ['email' => $email]);
            
            return [
                'success' => false,
                'message' => 'Invalid credentials.',
            ];
        }

        // Create token
        $token = $user->createToken('api_token')->plainTextToken;

        Log::info('User logged in successfully', ['user_id' => $user->id]);

        return [
            'success' => true,
            'user' => $user->load('roles'),
            'token' => $token,
            'message' => 'Login successful.',
        ];
    }

    /**
     * Logout user
     */
    public function logout(User $user): array
    {
        try {
            $user->currentAccessToken()->delete();

            Log::info('User logged out', ['user_id' => $user->id]);

            return [
                'success' => true,
                'message' => 'Logged out successfully.',
            ];

        } catch (Exception $e) {
            Log::error('Logout failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Send password reset link
     */
    public function sendResetLink(string $email): array
    {
        $status = Password::sendResetLink(['email' => $email]);

        if ($status === Password::RESET_LINK_SENT) {
            Log::info('Password reset link sent', ['email' => $email]);

            return [
                'success' => true,
                'message' => 'Password reset link sent to your email.',
            ];
        }

        Log::warning('Failed to send password reset link', [
            'email' => $email,
            'status' => $status,
        ]);

        return [
            'success' => false,
            'message' => 'Unable to send password reset link.',
        ];
    }

    /**
     * Reset password
     */
    public function resetPassword(array $credentials): array
    {
        $status = Password::reset(
            $credentials,
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));

                Log::info('Password reset successful', ['user_id' => $user->id]);
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return [
                'success' => true,
                'message' => 'Password has been reset successfully.',
            ];
        }

        Log::warning('Password reset failed', [
            'email' => $credentials['email'],
            'status' => $status,
        ]);

        return [
            'success' => false,
            'message' => 'Failed to reset password. Invalid or expired token.',
        ];
    }

    /**
     * Change password
     */
    public function changePassword(User $user, string $currentPassword, string $newPassword): array
    {
        if (!Hash::check($currentPassword, $user->password)) {
            Log::warning('Change password failed - wrong current password', [
                'user_id' => $user->id,
            ]);

            return [
                'success' => false,
                'message' => 'Current password is incorrect.',
            ];
        }

        try {
            $user->update([
                'password' => Hash::make($newPassword),
            ]);

            // Revoke all tokens for security
            $user->tokens()->delete();

            Log::info('Password changed successfully', ['user_id' => $user->id]);

            return [
                'success' => true,
                'message' => 'Password changed successfully. Please login again.',
            ];

        } catch (Exception $e) {
            Log::error('Change password failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get user profile
     */
    public function getProfile(User $user): array
    {
        return [
            'success' => true,
            'user' => $user->load('roles'),
            'roles' => $user->getRoleNames(),
        ];
    }
}
