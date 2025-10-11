<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($subject ?? 'FezaMarket'); ?></title>
    <style>
        /* Email-safe CSS */
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333333;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            padding: 0;
        }
        
        .email-header {
            background-color: #0064d2;
            color: white;
            padding: 20px;
            text-align: center;
        }
        
        .email-header h1 {
            margin: 0;
            font-size: 24px;
        }
        
        .email-content {
            padding: 30px;
        }
        
        .email-content h2 {
            color: #0064d2;
            margin-top: 0;
        }
        
        .email-content p {
            margin-bottom: 16px;
        }
        
        .btn {
            display: inline-block;
            background-color: #0064d2;
            color: white;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 4px;
            font-weight: bold;
            margin: 10px 0;
        }
        
        .btn-outline {
            background-color: white;
            color: #0064d2;
            border: 2px solid #0064d2;
        }
        
        .email-footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #666666;
            border-top: 1px solid #e5e5e5;
        }
        
        .email-footer a {
            color: #0064d2;
            text-decoration: none;
        }
        
        .info-box {
            background-color: #f8f9fa;
            border-left: 4px solid #0064d2;
            padding: 15px;
            margin: 20px 0;
        }
        
        .warning-box {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
        }
        
        .order-summary {
            border: 1px solid #e5e5e5;
            border-radius: 4px;
            padding: 15px;
            margin: 20px 0;
        }
        
        .order-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .order-item:last-child {
            border-bottom: none;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <h1>FezaMarket</h1>
        </div>
        
        <div class="email-content">
            <?php echo $content; ?>
        </div>
        
        <div class="email-footer">
            <p>
                <strong>FezaMarket</strong><br>
                Your trusted online marketplace
            </p>
            <p>
                <a href="<?php echo url(''); ?>">Visit FezaMarket</a> | 
                <a href="<?php echo url('help.php'); ?>">Help Center</a> | 
                <a href="<?php echo url('account.php?tab=preferences'); ?>">Email Preferences</a>
            </p>
            <p>
                This email was sent to <?php echo htmlspecialchars($user['email'] ?? ''); ?>. 
                If you no longer wish to receive these emails, you can 
                <a href="<?php echo url('unsubscribe.php?token=' . ($unsubscribe_token ?? '')); ?>">unsubscribe here</a>.
            </p>
            <p>© <?php echo date('Y'); ?> FezaMarket Inc. All rights reserved.</p>
        </div>
    </div>
</body>
</html>