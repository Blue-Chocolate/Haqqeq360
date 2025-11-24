<?php

namespace App\Repositories\Authentication;

use App\Models\User;

interface EmailVerificationServiceInterface
{
    public function sendVerificationEmail(User $user): void;
    public function verifyEmail(string $token): array;
    public function resendVerification(string $email): array;
}