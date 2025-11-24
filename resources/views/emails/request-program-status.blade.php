<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Program Request Status Update</title>
    <style>
        body {
            font-family: 'Arial', 'Helvetica', sans-serif;
            line-height: 1.6;
            color: #333333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .email-container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .email-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff;
            padding: 30px;
            text-align: center;
        }
        .email-header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .email-body {
            padding: 40px 30px;
        }
        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
            margin: 20px 0;
        }
        .status-approved {
            background-color: #d4edda;
            color: #155724;
        }
        .status-rejected {
            background-color: #f8d7da;
            color: #721c24;
        }
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        .info-section {
            background-color: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .info-row {
            display: flex;
            margin-bottom: 12px;
        }
        .info-row:last-child {
            margin-bottom: 0;
        }
        .info-label {
            font-weight: 600;
            color: #555555;
            min-width: 140px;
        }
        .info-value {
            color: #333333;
        }
        .features-list {
            list-style: none;
            padding: 0;
            margin: 10px 0;
        }
        .features-list li {
            padding: 8px 0;
            padding-left: 24px;
            position: relative;
        }
        .features-list li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #667eea;
            font-weight: bold;
        }
        .email-footer {
            background-color: #f8f9fa;
            padding: 20px 30px;
            text-align: center;
            border-top: 1px solid #e9ecef;
        }
        .email-footer p {
            margin: 5px 0;
            font-size: 12px;
            color: #6c757d;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
            font-weight: 600;
        }
        @media only screen and (max-width: 600px) {
            .email-container {
                margin: 10px;
            }
            .email-body {
                padding: 20px 15px;
            }
            .info-row {
                flex-direction: column;
            }
            .info-label {
                margin-bottom: 4px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <h1>Program Request Status Update</h1>
        </div>
        
        <div class="email-body">
            <p>Hello <strong>{{ $requestProgram->user->name }}</strong>,</p>
            
            <p>Your program request has been updated. Here are the details:</p>
            
            <div style="text-align: center;">
                <span class="status-badge status-{{ $requestProgram->status }}">
                    {{ strtoupper($requestProgram->status) }}
                </span>
            </div>
            
            <div class="info-section">
                <div class="info-row">
                    <span class="info-label">Program Name:</span>
                    <span class="info-value">{{ $requestProgram->program_name }}</span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Requested By:</span>
                    <span class="info-value">{{ $requestProgram->requested_by }}</span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Request Date:</span>
                    <span class="info-value">{{ $requestProgram->requested_date->format('F d, Y') }}</span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Status:</span>
                    <span class="info-value"><strong>{{ ucfirst($requestProgram->status) }}</strong></span>
                </div>
            </div>
            
            @if($requestProgram->description)
            <div style="margin: 20px 0;">
                <h3 style="color: #667eea; margin-bottom: 10px;">Description:</h3>
                <p style="background-color: #f8f9fa; padding: 15px; border-radius: 4px; margin: 0;">
                    {{ $requestProgram->description }}
                </p>
            </div>
            @endif
            
            @if($requestProgram->requested_features && count($requestProgram->requested_features) > 0)
            <div style="margin: 20px 0;">
                <h3 style="color: #667eea; margin-bottom: 10px;">Requested Features:</h3>
                <ul class="features-list">
                    @foreach($requestProgram->requested_features as $feature)
                        <li>{{ $feature }}</li>
                    @endforeach
                </ul>
            </div>
            @endif
            
            @if($requestProgram->status === 'approved')
                <p style="color: #155724; background-color: #d4edda; padding: 15px; border-radius: 4px; margin-top: 20px;">
                    <strong>Great news!</strong> Your program request has been approved. Our team will begin working on it shortly.
                </p>
            @elseif($requestProgram->status === 'rejected')
                <p style="color: #721c24; background-color: #f8d7da; padding: 15px; border-radius: 4px; margin-top: 20px;">
                    Unfortunately, your program request has been rejected. If you have any questions, please contact our support team.
                </p>
            @else
                <p style="color: #856404; background-color: #fff3cd; padding: 15px; border-radius: 4px; margin-top: 20px;">
                    Your program request is currently pending review. We'll notify you once there's an update.
                </p>
            @endif
            
            <div style="text-align: center;">
                <a href="{{ config('app.url') }}" class="button">View Dashboard</a>
            </div>
        </div>
        
        <div class="email-footer">
            <p><strong>{{ config('app.name') }}</strong></p>
            <p>This is an automated message. Please do not reply to this email.</p>
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>