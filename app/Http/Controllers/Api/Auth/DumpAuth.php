<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class DumpAuth extends Controller
{
    /**
     * Register a new user
     */
public function register(Request $request)
{
    $validator = Validator::make($request->all(), [
        'first_name'  => 'required|string|max:255',
        'second_name' => 'required|string|max:255',
        'email'       => 'required|email|unique:users,email',
        'phone'       => 'nullable|string',
        'password'    => 'required|min:6|confirmed', // requires password_confirmation
        'avatar'      => 'nullable', // can be file or string URL
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status'  => false,
            'message' => 'Validation error',
            'errors'  => $validator->errors()
        ], 422);
    }

    $avatarPath = null;

    // Check if avatar is a file upload
    if ($request->hasFile('avatar') && $request->file('avatar')->isValid()) {
        $avatarPath = $request->file('avatar')->store('avatars', 'public'); // storage/app/public/avatars
    }
    // Check if avatar is a URL string
    elseif ($request->input('avatar')) {
        $avatarPath = $request->input('avatar');
    }

    $user = User::create([
        'first_name'  => $request->first_name,
        'second_name' => $request->second_name,
        'email'       => $request->email,
        'phone'       => $request->phone,
        'password'    => Hash::make($request->password),
        'avatar'      => $avatarPath,
    ]);

    $token = $user->createToken('api_token')->plainTextToken;

    return response()->json([
        'status'  => true,
        'message' => 'User registered successfully',
        'token'   => $token,
        'user'    => $user,
    ]);
}

    /**
     * Login user
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'password' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation error',
                'errors'  => $validator->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status'  => false,
                'message' => 'Invalid email or password',
            ], 401);
        }

        // Delete old tokens
        $user->tokens()->delete();

        $token = $user->createToken('api_token')->plainTextToken;

        return response()->json([
            'status'  => true,
            'message' => 'Login successful',
            'token'   => $token,
            'user'    => $user,
        ]);
    }

    /**
     * Get authenticated user
     */
    public function me(Request $request)
    {
        return response()->json([
            'status' => true,
            'user'   => $request->user(),
        ]);
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Logged out successfully'
        ]);
    }
}
