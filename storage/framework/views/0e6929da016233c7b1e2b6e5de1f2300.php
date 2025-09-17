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
            padding: 20px;
            background-color: #f8f9fa;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header {
            background: linear-gradient(135deg, #2563EB 0%, #3B82F6 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .content {
            padding: 30px;
        }
        .badge {
            display: inline-block;
            padding: 8px 20px;
            background: linear-gradient(135deg, #2563EB 0%, #3B82F6 100%);
            color: white;
            border-radius: 25px;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 20px;
        }
        .info-box {
            background: #EFF6FF;
            border-left: 4px solid #2563EB;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .thank-section {
            background: linear-gradient(135deg, #DBEAFE 0%, #BFDBFE 100%);
            border-radius: 12px;
            padding: 25px;
            margin: 25px 0;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 style="margin: 0; font-size: 28px;">💡 Merci pour votre suggestion !</h1>
            <p style="margin: 10px 0 0 0; opacity: 0.9;"><?php echo e($company_name); ?></p>
        </div>

        <div class="content">
            <div class="badge">💡 Suggestion reçue</div>

            <p><strong>Bonjour <?php echo e($client_name); ?> !</strong></p>

            <p>Nous vous remercions sincèrement pour votre suggestion concernant nos services.</p>

            <div class="info-box">
                <div style="margin-bottom: 10px;">
                    <strong>Référence:</strong> <?php echo e($feedback_reference); ?>

                </div>
                <div style="margin-bottom: 10px;">
                    <strong>Votre note:</strong> <?php echo e($rating); ?>/5 ⭐
                </div>
                <div style="margin-bottom: 15px;">
                    <strong>Date:</strong> <?php echo e($created_at); ?>

                </div>

                <?php if($message): ?>
                <div style="background: white; border: 1px solid #BFDBFE; border-radius: 8px; padding: 15px; margin-top: 15px;">
                    <h4 style="margin: 0 0 10px 0; color: #2563EB;">Votre suggestion:</h4>
                    <p style="margin: 0; font-style: italic; color: #4B5563;">"<?php echo e($message); ?>"</p>
                </div>
                <?php endif; ?>
            </div>

            <div class="thank-section">
                <h3 style="color: #1E40AF; margin: 0 0 15px 0;">🙏 Votre avis compte pour nous</h3>
                <p style="margin: 0; color: #1E3A8A;">
                    Vos suggestions nous aident à améliorer continuellement nos services.<br>
                    Notre équipe va étudier attentivement votre proposition.
                </p>
            </div>

            <div style="margin: 20px 0; text-align: left;">
                <h4 style="color: #2563EB;">Que se passe-t-il ensuite ?</h4>
                <ul style="color: #4B5563; line-height: 1.8;">
                    <li>Notre équipe analyse votre suggestion</li>
                    <li>Nous évaluons la faisabilité de sa mise en œuvre</li>
                    <li>Vous serez informé(e) si nous décidons de l'implémenter</li>
                    <li>Votre contribution sera reconnue le cas échéant</li>
                </ul>
            </div>

            <div style="background: #F0F9FF; border: 1px solid #E0F2FE; border-radius: 8px; padding: 15px; margin: 20px 0; text-align: center;">
                <p style="margin: 0; color: #0F172A;">
                    💝 <strong>Bonus:</strong> Vous gagnez des KaliPoints pour cette suggestion constructive !
                </p>
            </div>

            <p style="text-align: center; margin-top: 30px; color: #6B7280;">
                Cordialement,<br>
                <strong>L'équipe <?php echo e($company_name); ?></strong>
            </p>
        </div>
    </div>
</body>
</html><?php /**PATH C:\Projet\qualywatch\backend\resources\views/emails/client-suggestion-thank-you.blade.php ENDPATH**/ ?>