<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

use App\Http\Controllers\Api\Auth\DumpAuth;

Route::post('/register', [DumpAuth::class, 'register']);
Route::post('/login',    [DumpAuth::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me',    [DumpAuth::class, 'me']);
    Route::post('/logout', [DumpAuth::class, 'logout']);
});
// Route::prefix('auth')->group(function () {
//     Route::post('/register', [AuthController::class, 'register']);
//     Route::post('/login', [AuthController::class, 'login']);
//     Route::post('/verify-email', [AuthController::class, 'verifyEmail']);
//     Route::post('/resend-verification', [AuthController::class, 'resendVerificationEmail']);
//     Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
//     Route::post('/reset-password', [AuthController::class, 'resetPassword']);
// });

// Protected routes (requires authentication)
Route::middleware('auth:sanctum')->prefix('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);
});


use App\Http\Controllers\Api\UserController\UserController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);
});

use App\Http\Controllers\Api\NotificationController\NotificationController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::get('notifications/{id}', [NotificationController::class, 'show']);
    Route::post('notifications', [NotificationController::class, 'store']);
    Route::patch('notifications/{id}/read', [NotificationController::class, 'markRead']);
    Route::patch('notifications/{id}/unread', [NotificationController::class, 'markUnread']);
    Route::delete('notifications/{id}', [NotificationController::class, 'destroy']);
});

use App\Http\Controllers\Api\BootcampController\BootcampController;

Route::get('bootcamps', [BootcampController::class, 'basicList']);
Route::get('bootcamps/{id}', [BootcampController::class, 'details']);

use App\Http\Controllers\Api\CourseController\CourseController;

Route::prefix('courses')->group(function () {

    // List all courses (paginated, optional limit)
    Route::get('/', [CourseController::class, 'index']);

    // Show a single course by ID
    Route::get('/{id}', [CourseController::class, 'show']);

    // Search courses by title
    Route::get('/search', [CourseController::class, 'search']);

    // Filter courses by level/mode
    Route::get('/filter', [CourseController::class, 'filter']);
});


use App\Http\Controllers\Api\ProgramController\ProgramController;
use App\Http\Controllers\Api\CategoryController\CategoryController;

// Programs routes
Route::prefix('programs')->group(function () {
    Route::get('/', [ProgramController::class, 'index']); // List programs
    Route::get('/{idOrSlug}', [ProgramController::class, 'show']); // Show program by ID or slug
});

// Categories routes
Route::prefix('categories')->group(function () {
    Route::get('/', [CategoryController::class, 'index']); // List categories
    Route::get('/{idOrSlug}', [CategoryController::class, 'show']); // Show category by ID or slug
});

use App\Http\Controllers\Api\RequestProgramController\RequestProgramController;


// API Routes - User can only create and view their requests
Route::middleware('auth:sanctum')->group(function () {
    Route::get('request-programs', [RequestProgramController::class, 'index']);
    Route::post('request-programs', [RequestProgramController::class, 'store']);
});

use App\Http\Controllers\Api\ContactUsController\ContactUsController;

Route::middleware('auth:sanctum')->group(function () {
    Route::post('contact-us', [ContactUsController::class, 'store']);
});
use App\Http\Controllers\Api\ScholarshipRequestController\ScholarshipRequestController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('scholarship-requests', [ScholarshipRequestController::class, 'index']);
    Route::post('scholarship-requests', [ScholarshipRequestController::class, 'store']);
});