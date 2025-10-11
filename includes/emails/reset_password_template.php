<?php
/**
 * Password Reset Email Template
 * Usage: Load with file_get_contents and replace placeholders
 * Placeholders: {{USERNAME}}, {{RESET_LINK}}, {{APP_NAME}}, {{APP_URL}}, {{IP_ADDRESS}}, {{YEAR}}, {{SUPPORT_EMAIL}}
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset - {{APP_NAME}}</title>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            line-height: 1.6; 
            color: #333; 
            background-color: #f4f4f4; 
            margin: 0; 
            padding: 0; 
        }
        .container { 
            max-width: 600px; 
            margin: 0 auto; 
            background: white; 
            border-radius: 10px; 
            overflow: hidden; 
            box-shadow: 0 0 20px rgba(0,0,0,0.1); 
        }
        .header { 
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); 
            color: white; 
            padding: 40px 30px; 
            text-align: center; 
        }
        .header h1 { 
            margin: 0; 
            font-size: 28px; 
            font-weight: 300; 
        }
        .content { 
            padding: 40px 30px; 
        }
        .content h2 {
            color: #333;
            margin-top: 0;
        }
        .button { 
            display: inline-block; 
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); 
            color: white !important; 
            padding: 15px 30px; 
            text-decoration: none; 
            border-radius: 5px; 
            font-weight: bold; 
            margin: 20px 0; 
        }
        .footer { 
            background: #f8f9fa; 
            padding: 30px; 
            text-align: center; 
            color: #6c757d; 
            font-size: 14px; 
        }
        .footer a { 
            color: #667eea; 
            text-decoration: none; 
        }
        .link-box {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            word-break: break-all;
            margin: 20px 0;
        }
        ul {
            padding-left: 20px;
        }
        ul li {
            margin-bottom: 8px;
        }
        @media (max-width: 600px) {
            .container { margin: 0; border-radius: 0; }
            .header, .content, .footer { padding: 20px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔒 Password Reset Request</h1>
            <p>{{APP_NAME}}</p>
        </div>
        
        <div class="content">
            <h2>Hello {{USERNAME}}!</h2>
            
            <p>We received a request to reset your password for your {{APP_NAME}} account. If you made this request, click the button below to reset your password:</p>
            
            <div style="text-align: center;">
                <a href="{{RESET_LINK}}" class="button">Reset Your Password</a>
            </div>
            
            <p>If the button doesn't work, you can copy and paste this link into your browser:</p>
            
            <div class="link-box">
                <a href="{{RESET_LINK}}">{{RESET_LINK}}</a>
            </div>
            
            <p><strong>Important Security Information:</strong></p>
            <ul>
                <li>This password reset link will expire in 1 hour</li>
                <li>If you didn't request this reset, please ignore this email</li>
                <li>Your password will not change unless you click the link above and complete the reset process</li>
                <li>This request was made from {{IP_ADDRESS}}</li>
                <li>For security, never share this link with anyone</li>
            </ul>
            
            <p>Need help? Contact our support team at <a href="mailto:{{SUPPORT_EMAIL}}">{{SUPPORT_EMAIL}}</a></p>
        </div>
        
        <div class="footer">
            <p>This email was sent from {{APP_NAME}}</p>
            <p><a href="{{APP_URL}}">Visit our website</a> | <a href="{{APP_URL}}/privacy">Privacy Policy</a> | <a href="{{APP_URL}}/contact">Contact Support</a></p>
            <p>&copy; {{YEAR}} {{APP_NAME}}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
