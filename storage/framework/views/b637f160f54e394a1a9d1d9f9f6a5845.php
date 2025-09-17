<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Merci pour votre suggestion</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
        }
        
        .header-container {
            text-align: center;
            padding: 0;
            background: linear-gradient(135deg, #EA580C 0%, #FB923C 100%);
            width: 100%;
            border-radius: 20px 20px 0 0;
        }
        
        .header-container h1 {
            margin: 0;
            padding: 30px;
            font-size: 28px;
            font-weight: bold;
            color: white;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
            text-align: center;
            width: 100%;
            display: block;
        }
        
        .email-container {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            margin: 20px auto;
        }
        
        .content {
            padding: 30px;
            text-align: center;
        }
        
        .info-card {
            background: #F8F9FA;
            border-radius: 20px;
            padding: 20px;
            margin: 20px 0;
            border-left: 4px solid #2563EB;
        }
    </style>
</head>
<body>
    <div class="header-container">
        <h1>Qualy<?php echo e($company_name ?? ''); ?></h1>
    </div>
    
    <div class="email-container">
        <div class="content">
            <p>Bonjour <strong><?php echo e($client_name); ?></strong>,</p>

            <p>Nous vous remercions sincèrement pour votre suggestion concernant nos services.</p>

            <div class="info-card">
                <div style="display: inline-block; padding: 6px 16px; border-radius: 25px; font-size: 12px; font-weight: bold; text-transform: uppercase; color: white; background: linear-gradient(135deg, #2563EB 0%, #3B82F6 100%); margin-bottom: 15px;">
                    💡 Suggestion
                </div>
                
                <div style="margin-bottom: 10px;">
                    <strong>Votre note:</strong> <?php echo e($rating); ?>/5 ⭐
                </div>
                <div style="margin-bottom: 15px;">
                    <strong>Date:</strong> <?php echo e($created_at); ?>

                </div>

                <?php if($feedback_message ?? false): ?>
                <div style="background: #EFF6FF; border: 1px solid #BFDBFE; border-radius: 8px; padding: 15px; margin: 15px 0;">
                    <h4 style="margin: 0 0 10px 0; color: #2563EB;">Votre suggestion:</h4>
                    <p style="margin: 0; font-style: italic;">"<?php echo e($feedback_message); ?>"</p>
                </div>
                <?php endif; ?>
            </div>

            <div style="background: white; border: 3px solid #EA580C; border-radius: 8px; padding: 20px; margin: 25px 0; text-align: center;">
                <h3 style="color: #333; margin: 0 0 15px 0;">Votre avis compte pour nous</h3>
                <p style="margin: 0; color: #333;">
                    Vos suggestions nous aident à améliorer continuellement nos services.
                </p>
            </div>

            <p>Cordialement,<br>L'équipe Qualy<?php echo e($company_name ?? ''); ?></p>
        </div>
    </div>
</body>
</html><?php /**PATH C:\Projet\qualywatch\backend\resources\views/emails/suggestion-thank-you-simple.blade.php ENDPATH**/ ?>