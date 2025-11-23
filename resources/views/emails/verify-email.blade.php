<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Your Email</title>
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
            border-bottom: 2px solid #4CAF50;
        }
        .header h1 {
            color: #4CAF50;
            margin: 0;
        }
        .content {
            padding: 30px 0;
        }
        .button {
            display: inline-block;
            padding: 15px 30px;
            background: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
            font-weight: bold;
        }
        .button:hover {
            background: #45a049;
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
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎓 Haqq Academy</h1>
            <p>Email Verification</p>
        </div>
        
        <div class="content">
            <h2>Hello {{ $user->first_name }}!</h2>
            
            <p>Thank you for registering with Haqq Academy. We're excited to have you join our learning community!</p>
            
            <p>Please click the button below to verify your email address:</p>
            
            <div style="text-align: center;">
                <a href="{{ $verificationUrl }}" class="button">Verify Email Address</a>
            </div>
            
            <p>Or copy and paste this link into your browser:</p>
            <div class="token-box">
                {{ $verificationUrl }}
            </div>
            
            <p><strong>Note:</strong> This verification link will expire in 24 hours.</p>
            
            <p>If you didn't create an account with Haqq Academy, please ignore this email.</p>
        </div>
        
        <div class="footer">
            <p>© {{ date('Y') }} Haqq Academy. All rights reserved.</p>
            <p>This is an automated email, please do not reply.</p>
        </div>
    </div>
</body>
</html>
