<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Merci pour votre suggestion</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #2563EB; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: white; padding: 30px; border: 1px solid #ddd; border-radius: 0 0 8px 8px; }
        .badge { background: #2563EB; color: white; padding: 8px 16px; border-radius: 20px; display: inline-block; font-size: 14px; margin-bottom: 20px; }
        .info-box { background: #f8f9fa; border-left: 4px solid #2563EB; padding: 20px; margin: 20px 0; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>💡 Merci pour votre suggestion !</h1>
        </div>

        <div class="content">
            <div class="badge">💡 Suggestion reçue</div>

            <p><strong>Bonjour <?php echo e($client_name); ?> !</strong></p>

            <p>Nous vous remercions sincèrement pour votre suggestion concernant nos services.</p>

            <div class="info-box">
                <p><strong>Référence:</strong> <?php echo e($feedback_reference); ?></p>
                <p><strong>Votre note:</strong> <?php echo e($rating); ?>/5 ⭐</p>
                <p><strong>Date:</strong> <?php echo e($created_at); ?></p>

                <?php if(!empty($description)): ?>
                <div style="background: white; border: 1px solid #ddd; padding: 15px; margin-top: 15px; border-radius: 4px;">
                    <h4 style="color: #2563EB; margin-top: 0;">Votre suggestion:</h4>
                    <p style="margin-bottom: 0; font-style: italic;">"<?php echo e($description); ?>"</p>
                </div>
                <?php endif; ?>
            </div>

            <div style="background: linear-gradient(135deg, #DBEAFE 0%, #BFDBFE 100%); padding: 25px; border-radius: 8px; text-align: center; margin: 25px 0;">
                <h3 style="color: #1E40AF; margin-top: 0;">🙏 Votre avis compte pour nous</h3>
                <p style="margin-bottom: 0; color: #1E3A8A;">
                    Vos suggestions nous aident à améliorer continuellement nos services.<br>
                    Notre équipe va étudier attentivement votre proposition.
                </p>
            </div>

            <div style="margin: 20px 0;">
                <h4 style="color: #2563EB;">Que se passe-t-il ensuite ?</h4>
                <ul style="color: #666;">
                    <li>Notre équipe analyse votre suggestion</li>
                    <li>Nous évaluons la faisabilité de sa mise en œuvre</li>
                    <li>Vous serez informé(e) si nous décidons de l'implémenter</li>
                    <li>Votre contribution sera reconnue le cas échéant</li>
                </ul>
            </div>

            <div style="background: #f0f9ff; border: 1px solid #e0f2fe; padding: 15px; text-align: center; border-radius: 4px;">
                <p style="margin: 0;">💝 <strong>Bonus:</strong> Vous gagnez des KaliPoints pour cette suggestion constructive !</p>
            </div>

            <p style="text-align: center; margin-top: 30px; color: #666;">
                Cordialement,<br>
                <strong>L'équipe <?php echo e($company_name); ?></strong>
            </p>
        </div>
    </div>
</body>
</html><?php /**PATH C:\Projet\qualywatch\backend\resources\views/emails/client-suggestion-simple.blade.php ENDPATH**/ ?>