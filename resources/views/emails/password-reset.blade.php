<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Your Password</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .container {
            background: #f9f9f9;
            border-radius: 10px;
            padding: 30px;
            border: 1px solid #ddd;
        }
        .header {
            text-align: center;
            padding-bottom: 20px;
            border-bottom: 2px solid #FF5722;
        }
        .header h1 {
            color: #FF5722;
            margin: 0;
        }
        .content {
            padding: 30px 0;
        }
        .button {
            display: inline-block;
            padding: 15px 30px;
            background: #FF5722;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
            font-weight: bold;
        }
        .button:hover {
            background: #E64A19;
        }
        .footer {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            color: #777;
            font-size: 12px;
        }
        .token-box {
            background: #fff;
            border: 1px dashed #ccc;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
            font-family: monospace;
            word-break: break-all;
        }
        .warning {
            background: #FFF3E0;
            border-left: 4px solid #FF9800;
            padding: 15px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 Haqq Academy</h1>
            <p>Password Reset Request</p>
        </div>
        
        <div class="content">
            <h2>Hello {{ $user->first_name }}!</h2>
            
            <p>We received a request to reset your password for your Haqq Academy account.</p>
            
            <p>Click the button below to reset your password:</p>
            
            <div style="text-align: center;">
                <a href="{{ $resetUrl }}" class="button">Reset Password</a>
            </div>
            
            <p>Or copy and paste this link into your browser:</p>
            <div class="token-box">
                {{ $resetUrl }}
            </div>
            
            <div class="warning">
                <strong>⚠️ Security Notice:</strong>
                <ul style="margin: 10px 0;">
                    <li>This link will expire in 24 hours</li>
                    <li>If you didn't request this reset, please ignore this email</li>
                    <li>Your password will remain unchanged until you create a new one</li>
                </ul>
            </div>
            
            <p>If you're having trouble, please contact our support team at <strong>{{ env('MAIL_FROM_ADDRESS') }}</strong></p>
        </div>
        
        <div class="footer">
            <p>© {{ date('Y') }} Haqq Academy. All rights reserved.</p>
            <p>This is an automated email, please do not reply.</p>
        </div>
    </div>
</body>
</html>