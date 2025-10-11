<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wallet Notification</title>
</head>
<body style="margin: 0; padding: 0; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f7f7f7;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f7f7f7; padding: 40px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="background: <?= ($type ?? 'credit') === 'credit' ? 'linear-gradient(135deg, #11998e 0%, #38ef7d 100%)' : 'linear-gradient(135deg, #ee0979 0%, #ff6a00 100%)' ?>; padding: 40px; text-align: center; border-radius: 8px 8px 0 0;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 28px; font-weight: 600;">
                                <?= ($type ?? 'credit') === 'credit' ? '💰 Wallet Credited' : '💸 Wallet Debited' ?>
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
                                Your wallet has been <?= ($type ?? 'credit') === 'credit' ? 'credited with' : 'debited for' ?>:
                            </p>
                            
                            <div style="background-color: <?= ($type ?? 'credit') === 'credit' ? '#e8f5e9' : '#ffebee' ?>; padding: 20px; margin: 20px 0; text-align: center; border-radius: 8px;">
                                <p style="margin: 0; color: #333333; font-size: 36px; font-weight: 700;">
                                    <?php 
                                    $currencyService = new CurrencyService();
                                    echo $currencyService->format($amount ?? 0, $currency ?? 'USD');
                                    ?>
                                </p>
                            </div>
                            
                            <?php if (!empty($description)): ?>
                            <p style="margin: 0 0 15px 0; color: #666666; font-size: 16px; line-height: 1.6;">
                                <strong>Description:</strong> <?= htmlspecialchars($description) ?>
                            </p>
                            <?php endif; ?>
                            
                            <?php if (!empty($reference)): ?>
                            <p style="margin: 0 0 15px 0; color: #999999; font-size: 14px;">
                                <strong>Reference:</strong> <?= htmlspecialchars($reference) ?>
                            </p>
                            <?php endif; ?>
                            
                            <div style="background-color: #f9f9f9; padding: 20px; margin: 25px 0; border-radius: 8px;">
                                <p style="margin: 0; color: #666666; font-size: 16px;">
                                    <strong>New Balance:</strong> 
                                    <span style="color: #333333; font-size: 20px; font-weight: 600;">
                                        <?= $currencyService->format($newBalance ?? 0, $currency ?? 'USD') ?>
                                    </span>
                                </p>
                            </div>
                            
                            <div style="text-align: center; margin: 30px 0;">
                                <a href="<?= env('APP_URL', 'https://fezamarket.com') ?>/account.php" 
                                   style="display: inline-block; padding: 15px 40px; background-color: #667eea; color: #ffffff; text-decoration: none; border-radius: 5px; font-weight: 600; font-size: 16px;">
                                    View Wallet
                                </a>
                            </div>
                            
                            <p style="margin: 25px 0 0 0; color: #999999; font-size: 14px; line-height: 1.6;">
                                Transaction Date: <?= date('F j, Y \a\t g:i A') ?>
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
