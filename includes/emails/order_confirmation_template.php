<?php
/**
 * Order Confirmation Email Template
 * Usage: Load with file_get_contents and replace placeholders
 * Placeholders: {{USERNAME}}, {{ORDER_NUMBER}}, {{ORDER_DATE}}, {{ORDER_TOTAL}}, {{ORDER_ITEMS}}, {{TRACKING_URL}}, {{APP_NAME}}, {{APP_URL}}, {{YEAR}}, {{SUPPORT_EMAIL}}
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - {{APP_NAME}}</title>
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
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%); 
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
        .order-details {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .order-details h3 {
            margin-top: 0;
            color: #495057;
        }
        .order-info {
            display: table;
            width: 100%;
            margin: 10px 0;
        }
        .order-info-row {
            display: table-row;
        }
        .order-info-label {
            display: table-cell;
            padding: 5px 10px 5px 0;
            font-weight: bold;
            width: 150px;
        }
        .order-info-value {
            display: table-cell;
            padding: 5px 0;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .items-table th {
            background: #e9ecef;
            padding: 12px;
            text-align: left;
            border-bottom: 2px solid #dee2e6;
        }
        .items-table td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
        }
        .button { 
            display: inline-block; 
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%); 
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
        .total {
            font-size: 18px;
            font-weight: bold;
            color: #28a745;
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
            .order-info-label { width: 120px; font-size: 14px; }
            .items-table th, .items-table td { padding: 8px; font-size: 14px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✅ Order Confirmed!</h1>
            <p>Thank you for your order</p>
        </div>
        
        <div class="content">
            <h2>Hello {{USERNAME}}!</h2>
            
            <p>Thank you for your order! We've received your order and will process it shortly.</p>
            
            <div class="order-details">
                <h3>Order Details</h3>
                <div class="order-info">
                    <div class="order-info-row">
                        <div class="order-info-label">Order Number:</div>
                        <div class="order-info-value"><strong>{{ORDER_NUMBER}}</strong></div>
                    </div>
                    <div class="order-info-row">
                        <div class="order-info-label">Order Date:</div>
                        <div class="order-info-value">{{ORDER_DATE}}</div>
                    </div>
                    <div class="order-info-row">
                        <div class="order-info-label">Order Total:</div>
                        <div class="order-info-value total">{{ORDER_TOTAL}}</div>
                    </div>
                </div>
            </div>
            
            <h3>Items Ordered</h3>
            {{ORDER_ITEMS}}
            
            <div style="text-align: center;">
                <a href="{{TRACKING_URL}}" class="button">Track Your Order</a>
            </div>
            
            <p><strong>What happens next?</strong></p>
            <ul>
                <li>We'll send you an email when your order ships</li>
                <li>You can track your order status using the button above</li>
                <li>If you have any questions, our support team is here to help</li>
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
