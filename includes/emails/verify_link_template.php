<?php
/**
 * Email Verification Link Template
 * Usage: Load with file_get_contents and replace {{VERIFY_LINK}} placeholder
 * Placeholders: {{USERNAME}}, {{VERIFY_LINK}}, {{APP_NAME}}, {{APP_URL}}, {{YEAR}}
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify your FezaMarket account</title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
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
        .button-container { 
            text-align: center; 
            margin: 30px 0; 
        }
        .button { 
            display: inline-block; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            color: white !important; 
            padding: 16px 40px; 
            text-decoration: none; 
            border-radius: 8px; 
            font-weight: bold; 
            font-size: 18px;
            margin: 20px 0; 
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }
        .button:hover {
            box-shadow: 0 6px 16px rgba(102, 126, 234, 0.4);
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
        .warning-box {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 5px;
            padding: 15px;
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
            .button { font-size: 16px; padding: 14px 30px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🛡️ Verify Your Email</h1>
            <p>Welcome to {{APP_NAME}}!</p>
        </div>
        
        <div class="content">
            <h2>Hello {{USERNAME}}!</h2>
            
            <p>Thank you for creating your account with {{APP_NAME}}. To complete your registration and secure your account, please click the button below to verify your email address:</p>
            
            <div class="button-container">
                <a href="{{VERIFY_LINK}}" class="button">✅ Verify Now</a>
            </div>
            
            <p><small>Or copy and paste this link into your browser:</small><br>
            <a href="{{VERIFY_LINK}}" style="color: #667eea; word-break: break-all;">{{VERIFY_LINK}}</a></p>
            
            <div class="warning-box">
                <p><strong>⚠️ Important Security Notice:</strong></p>
                <ul style="margin: 10px 0;">
                    <li>This link must be opened from the <strong>same device and network</strong> you used to register</li>
                    <li>The link will expire in <strong>24 hours</strong></li>
                    <li>This link can only be used once</li>
                </ul>
            </div>
            
            <p><strong>Security Information:</strong></p>
            <ul>
                <li>If you didn't create this account, please ignore this email</li>
                <li>Never share this verification link with anyone</li>
                <li>This link expires after 24 hours for your security</li>
            </ul>
        </div>
        
        <div class="footer">
            <p>This email was sent from {{APP_NAME}}</p>
            <p><a href="{{APP_URL}}">Visit our website</a> | <a href="{{APP_URL}}/privacy.php">Privacy Policy</a> | <a href="{{APP_URL}}/contact.php">Contact Support</a></p>
            <p>&copy; {{YEAR}} {{APP_NAME}}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
