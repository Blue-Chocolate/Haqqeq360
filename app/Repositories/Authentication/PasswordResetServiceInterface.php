<?php

namespace App\Repositories\Authentication;

interface PasswordResetServiceInterface
{
    public function sendResetEmail(string $email): array;
    public function resetPassword(string $email, string $token, string $newPassword): array;
    public function verifyResetToken(string $email, string $token): array;
}