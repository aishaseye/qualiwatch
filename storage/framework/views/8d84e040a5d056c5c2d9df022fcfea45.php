<?php $__env->startComponent('emails.layout'); ?>
    <?php $__env->slot('title', 'Nouveau Feedback Reçu'); ?>
    <?php $__env->slot('company_name', $company->name); ?>
    <?php $__env->slot('header_subtitle', ''); ?>

    <?php if($isNegative): ?>
    **Attention !** Un client a laissé un feedback négatif qui nécessite votre attention immédiate.
    <?php else: ?>
    Vous avez reçu un nouveau feedback de la part d'un client.
    <?php endif; ?>

    <div class="info-card">
        <div style="text-align: center; margin-bottom: 20px;">
            <div style="font-size: 32px; font-weight: bold; letter-spacing: 8px; color: #EA580C; text-align: center; font-family: monospace; background: linear-gradient(135deg, #EA580C 0%, #FB923C 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
                <?php echo e($feedback->rating); ?>/5
            </div>
        </div>

        <div style="margin-bottom: 10px;">
            <strong>Client:</strong> <?php echo e($client->full_name ?? 'Client Anonyme'); ?>

        </div>
        <div style="margin-bottom: 10px;">
            <strong>Type:</strong> <?php echo e($feedbackType); ?>

        </div>
        <div style="margin-bottom: 10px;">
            <strong>Note:</strong> <?php echo e($ratingStars); ?>

        </div>
        <div style="margin-bottom: 15px;">
            <strong>Date:</strong> <?php echo e($feedback->created_at->format('d/m/Y à H:i')); ?>

        </div>

        <?php if($feedback->service): ?>
        <div style="margin-bottom: 10px;">
            <strong>Service:</strong> <?php echo e($feedback->service->name); ?>

        </div>
        <?php endif; ?>

        <?php if($feedback->employee): ?>
        <div style="margin-bottom: 10px;">
            <strong>Employé:</strong> <?php echo e($feedback->employee->full_name); ?>

        </div>
        <?php endif; ?>

        <?php if($feedback->message): ?>
        <div style="background: #FEF3E2; border: 1px solid #FDBA74; border-radius: 8px; padding: 15px; margin: 15px 0;">
            <h4 style="margin: 0 0 10px 0; color: #EA580C;">Message du client:</h4>
            <p style="margin: 0; font-style: italic;">"<?php echo e($feedback->message); ?>"</p>
        </div>
        <?php endif; ?>
    </div>

    <?php if($isNegative): ?>
    <div style="background: #FEE2E2; border: 1px solid #FECACA; border-radius: 8px; padding: 15px; margin: 20px 0; text-align: center;">
        <strong style="color: #DC2626;">Actions recommandées - Priorité élevée:</strong><br>
        <span style="color: #991B1B;">
            • Contactez le client dans les plus brefs délais<br>
            • Identifiez les causes du problème<br>
            • Mettez en place des corrections immédiates<br>
            • Documentez les actions prises
        </span>
    </div>

    <div class="text-center mt-20">
        <a href="<?php echo e(config('app.frontend_url', config('app.url')) . '/dashboard/feedbacks/' . $feedback->id); ?>" class="btn-orange" style="background: linear-gradient(135deg, #DC2626 0%, #EF4444 100%);">
            🚨 Traiter ce feedback urgent
        </a>
    </div>
    <?php else: ?>
    <div class="text-center mt-20">
        <a href="<?php echo e(config('app.frontend_url', config('app.url')) . '/dashboard/feedbacks/' . $feedback->id); ?>" class="btn-orange">
            📝 Voir le feedback
        </a>
    </div>
    <?php endif; ?>

    <div style="margin-top: 30px; background: #F3F4F6; padding: 15px; border-radius: 8px;">
        <strong>Vos statistiques:</strong><br>
        • **Satisfaction générale:** <?php echo e(number_format($company->satisfaction_score, 1)); ?>/5 ⭐<br>
        • **Total feedbacks:** <?php echo e($company->total_feedbacks); ?>

    </div>

<?php echo $__env->renderComponent(); ?><?php /**PATH C:\Projet\qualywatch\backend\resources\views\emails\feedback-notification-clean.blade.php ENDPATH**/ ?>