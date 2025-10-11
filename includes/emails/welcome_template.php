<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to <?= env('APP_NAME', 'FezaMarket') ?></title>
</head>
<body style="margin: 0; padding: 0; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f7f7f7;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f7f7f7; padding: 40px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 40px; text-align: center; border-radius: 8px 8px 0 0;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 28px; font-weight: 600;">
                                Welcome to <?= env('APP_NAME', 'FezaMarket') ?>! 🎉
                            </h1>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px;">
                            <h2 style="margin: 0 0 20px 0; color: #333333; font-size: 22px;">
                                Hi <?= htmlspecialchars($userName ?? 'there') ?>!
                            </h2>
                            
                            <p style="margin: 0 0 15px 0; color: #666666; font-size: 16px; line-height: 1.6;">
                                Thank you for joining our community! We're excited to have you on board.
                            </p>
                            
                            <p style="margin: 0 0 15px 0; color: #666666; font-size: 16px; line-height: 1.6;">
                                Here's what you can do now:
                            </p>
                            
                            <ul style="color: #666666; font-size: 16px; line-height: 1.8; margin: 0 0 25px 0;">
                                <li>Browse thousands of products</li>
                                <li>Track your orders in real-time</li>
                                <li>Save your favorite items to wishlist</li>
                                <li>Get exclusive deals and offers</li>
                            </ul>
                            
                            <div style="text-align: center; margin: 30px 0;">
                                <a href="<?= env('APP_URL', 'https://fezamarket.com') ?>" 
                                   style="display: inline-block; padding: 15px 40px; background-color: #667eea; color: #ffffff; text-decoration: none; border-radius: 5px; font-weight: 600; font-size: 16px;">
                                    Start Shopping
                                </a>
                            </div>
                            
                            <p style="margin: 25px 0 0 0; color: #999999; font-size: 14px; line-height: 1.6;">
                                If you have any questions, feel free to reach out to our support team at 
                                <a href="mailto:<?= env('SUPPORT_EMAIL', 'support@fezamarket.com') ?>" style="color: #667eea; text-decoration: none;">
                                    <?= env('SUPPORT_EMAIL', 'support@fezamarket.com') ?>
                                </a>
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f9f9f9; padding: 30px; text-align: center; border-radius: 0 0 8px 8px; border-top: 1px solid #eeeeee;">
                            <p style="margin: 0 0 10px 0; color: #999999; font-size: 14px;">
                                © <?= date('Y') ?> <?= env('APP_NAME', 'FezaMarket') ?>. All rights reserved.
                            </p>
                            <p style="margin: 0; color: #cccccc; font-size: 12px;">
                                <?= env('APP_URL', 'https://fezamarket.com') ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
