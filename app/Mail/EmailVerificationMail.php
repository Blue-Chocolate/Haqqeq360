<?php 

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EmailVerificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $verificationToken;
    public $verificationUrl;

    public function __construct(User $user, string $verificationToken)
    {
        $this->user = $user;
        $this->verificationToken = $verificationToken;
        // Update this URL to match your frontend route
        $this->verificationUrl = env('FRONTEND_URL', 'http://localhost:3000') 
            . '/verify-email?token=' . $verificationToken 
            . '&email=' . urlencode($user->email);
    }

    public function build()
    {
        return $this->subject('Verify Your Email - Haqq Academy')
                    ->view('emails.verify-email');
    }
}