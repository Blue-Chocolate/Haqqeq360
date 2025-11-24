<?php 
namespace App\Repositories\Authentication;

use App\Models\User;

interface AuthServiceInterface
{
    public function register(array $data): array;
    public function login(array $credentials): array;
    public function logout(User $user): void;
    public function changePassword(User $user, string $currentPassword, string $newPassword): array;
}

// ============================================================================
// FILE: app/Contracts/EmailVerificationServiceInterface.php
// ============================================================================

namespace App\Contracts;

use App\Models\User;

interface EmailVerificationServiceInterface
{
    public function sendVerificationEmail(User $user): void;
    public function verifyEmail(string $token): array;
    public function resendVerification(string $email): array;
}