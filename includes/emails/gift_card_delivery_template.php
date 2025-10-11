<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gift Card Received</title>
</head>
<body style="margin: 0; padding: 0; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f7f7f7;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f7f7f7; padding: 40px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); padding: 40px; text-align: center; border-radius: 8px 8px 0 0;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 28px; font-weight: 600;">
                                🎁 You've Received a Gift Card!
                            </h1>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px;">
                            <h2 style="margin: 0 0 20px 0; color: #333333; font-size: 22px;">
                                Hi <?= htmlspecialchars($receiverName ?? 'there') ?>!
                            </h2>
                            
                            <?php if (!empty($senderName)): ?>
                            <p style="margin: 0 0 15px 0; color: #666666; font-size: 16px; line-height: 1.6;">
                                <strong><?= htmlspecialchars($senderName) ?></strong> has sent you a gift card worth 
                                <strong style="color: #f5576c;"><?= $currencyService->format($amount ?? 0, $currency ?? 'USD') ?></strong>!
                            </p>
                            <?php endif; ?>
                            
                            <?php if (!empty($message)): ?>
                            <div style="background-color: #f9f9f9; border-left: 4px solid #f5576c; padding: 15px; margin: 20px 0; border-radius: 4px;">
                                <p style="margin: 0; color: #666666; font-size: 15px; font-style: italic;">
                                    "<?= htmlspecialchars($message) ?>"
                                </p>
                            </div>
                            <?php endif; ?>
                            
                            <div style="background-color: #fff8f0; border: 2px dashed #f5576c; padding: 25px; margin: 25px 0; text-align: center; border-radius: 8px;">
                                <p style="margin: 0 0 10px 0; color: #999999; font-size: 14px; text-transform: uppercase; letter-spacing: 1px;">
                                    Your Gift Card Code
                                </p>
                                <p style="margin: 0; color: #333333; font-size: 32px; font-weight: 700; letter-spacing: 3px; font-family: 'Courier New', monospace;">
                                    <?= htmlspecialchars($giftCardCode ?? 'XXXX-XXXX-XXXX') ?>
                                </p>
                            </div>
                            
                            <p style="margin: 0 0 15px 0; color: #666666; font-size: 16px; line-height: 1.6;">
                                To redeem your gift card:
                            </p>
                            
                            <ol style="color: #666666; font-size: 16px; line-height: 1.8; margin: 0 0 25px 0;">
                                <li>Visit our website and log in to your account</li>
                                <li>Go to "My Wallet" or apply the code at checkout</li>
                                <li>Enter the gift card code above</li>
                                <li>The amount will be added to your wallet balance!</li>
                            </ol>
                            
                            <div style="text-align: center; margin: 30px 0;">
                                <a href="<?= env('APP_URL', 'https://fezamarket.com') ?>/account.php" 
                                   style="display: inline-block; padding: 15px 40px; background-color: #f5576c; color: #ffffff; text-decoration: none; border-radius: 5px; font-weight: 600; font-size: 16px;">
                                    Redeem Gift Card
                                </a>
                            </div>
                            
                            <p style="margin: 25px 0 0 0; color: #999999; font-size: 14px; line-height: 1.6;">
                                Note: Keep this code safe! Gift cards are non-refundable and cannot be replaced if lost.
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
