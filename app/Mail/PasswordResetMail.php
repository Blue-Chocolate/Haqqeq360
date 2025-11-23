<?php 


namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $resetToken;
    public $resetUrl;

    public function __construct(User $user, string $resetToken)
    {
        $this->user = $user;
        $this->resetToken = $resetToken;
        // Update this URL to match your frontend route
        $this->resetUrl = env('FRONTEND_URL', 'http://localhost:3000') 
            . '/reset-password?token=' . $resetToken 
            . '&email=' . urlencode($user->email);
    }

    public function build()
    {
        return $this->subject('Reset Your Password - Haqq Academy')
                    ->view('emails.password-reset');
    }
}
