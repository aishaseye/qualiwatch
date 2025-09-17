<?php $__env->startComponent('emails.layout', [
    'title' => ($isNegative ? 'Feedback Négatif Reçu' : 'Nouveau Feedback') . ' - Qualy' . $company->name,
    'company_name' => $company->name,
    'header_subtitle' => $isNegative ? 'Feedback Négatif - Action Requise' : 'Nouveau Feedback Reçu',
    'reference' => $feedback->display_id
]); ?>

<div class="text-center mb-20">
    <h2 style="color: #333; margin-bottom: 10px;">Bonjour <?php echo e($company->name); ?></h2>
    <p style="color: #666; font-size: 16px;">
        <?php if($isNegative): ?>
        <strong>Attention !</strong> Un client a laissé un feedback négatif qui nécessite votre attention immédiate.
        <?php else: ?>
        Vous avez reçu un nouveau feedback de la part d'un client.
        <?php endif; ?>
    </p>
</div>

<h3 style="color: #EA580C; margin-bottom: 20px;">Détails du Feedback</h3>

<div class="info-card" style="background-color: <?php echo e($urgencyLevel['bg']); ?>; border-left: 4px solid <?php echo e($urgencyLevel['color']); ?>;">
    <!-- Note centrée dans une card -->
    <div style="text-align: center; margin-bottom: 20px;">
        <div style="background: white; padding: 15px; border-radius: 8px; display: inline-block; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); border: 2px solid <?php echo e($urgencyLevel['color']); ?>;">
            <div style="font-size: 28px; margin-bottom: 5px;"><?php echo e($ratingStars); ?></div>
            <div style="font-size: 20px; font-weight: bold; color: <?php echo e($urgencyLevel['color']); ?>;">
                <?php echo e($feedback->rating); ?>/5
            </div>
            <div style="font-size: 12px; color: <?php echo e($urgencyLevel['color']); ?>; margin-top: 5px; font-weight: bold;">
                <?php echo e($urgencyLevel['level']); ?>

            </div>
        </div>
    </div>
    
    <div style="margin-bottom: 10px;">
        <strong>Client:</strong> <?php echo e($client->full_name ?? 'Client Anonyme'); ?><br>
        <strong>Type:</strong> <?php echo e($feedbackType); ?><br>
        <strong>Date:</strong> <?php echo e($feedback->created_at->format('d/m/Y à H:i')); ?>

    </div>
    
    <?php if($feedback->service): ?>
    <div style="margin-bottom: 10px;">
        <strong>Service concerné:</strong> 
        <span style="color: <?php echo e($feedback->service->color); ?>; font-weight: bold;">
            <?php echo e($feedback->service->name); ?>

        </span>
    </div>
    <?php endif; ?>
    
    <?php if($feedback->employee): ?>
    <div style="margin-bottom: 10px;">
        <strong>Employé concerné:</strong> <?php echo e($feedback->employee->full_name); ?>

    </div>
    <?php endif; ?>
</div>

<h3 style="color: #EA580C; margin-bottom: 20px; margin-top: 30px;">Message du Client</h3>

<div class="info-card">
    <div style="font-style: italic; padding: 15px; background-color: #F9FAFB; border-left: 4px solid #6B7280; border-radius: 8px;">
        "<?php echo e($feedback->message); ?>"
    </div>
</div>

<?php if($isNegative): ?>
<h3 style="color: #EA580C; margin-bottom: 20px; margin-top: 30px;">Actions Recommandées</h3>

<div class="info-card" style="border-left-color: #DC2626;">
    <p style="color: #DC2626; font-weight: bold; margin-bottom: 15px;">Priorité élevée - Réponse dans les 2h recommandée:</p>
    
    <ol style="color: #333; line-height: 1.8;">
        <li><strong>Contactez le client</strong> pour comprendre le problème</li>
        <li><strong>Identifiez les causes</strong> du dysfonctionnement</li>
        <li><strong>Mettez en place des corrections</strong> immédiates</li>
        <li><strong>Suivez l'évolution</strong> de la satisfaction client</li>
    </ol>
</div>

<div class="text-center mt-20">
    <a href="<?php echo e(config('app.frontend_url', config('app.url')) . '/dashboard/feedbacks/' . $feedback->id); ?>" class="btn-orange" style="background: linear-gradient(135deg, #DC2626 0%, #EF4444 100%);">
        Traiter ce Feedback Urgent
    </a>
</div>
<?php else: ?>
<div class="text-center mt-20">
    <a href="<?php echo e(config('app.frontend_url', config('app.url')) . '/dashboard/feedbacks/' . $feedback->id); ?>" class="btn-orange">
        Voir le Feedback
    </a>
</div>
<?php endif; ?>

<h3 style="color: #EA580C; margin-bottom: 20px; margin-top: 30px;">Statistiques Rapides</h3>

<div class="info-card">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;">
        <div style="margin-bottom: 10px;">
            <strong>Satisfaction générale:</strong> <?php echo e(number_format($company->satisfaction_score, 1)); ?>/5 ⭐
        </div>
        <div style="margin-bottom: 10px;">
            <strong>Total feedbacks:</strong> <?php echo e($company->total_feedbacks); ?> retours reçus
        </div>
    </div>
</div>

<?php if($isNegative): ?>
<div style="background-color: #FEE2E2; padding: 15px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #DC2626;">
    <strong style="color: #DC2626;">Rappel Important</strong><br>
    Les feedbacks négatifs impactent directement votre réputation. Une réponse rapide et efficace peut transformer une expérience négative en opportunité de fidélisation.
</div>
<?php endif; ?>

<div class="text-center mt-20">
    <p style="color: #666; font-size: 14px; margin-bottom: 10px;">
        <strong>Qualy<?php echo e($company->name); ?></strong> - Votre solution de gestion des retours clients
    </p>
    <p style="color: #999; font-size: 12px;">
        Transformez chaque feedback en opportunité d'amélioration
    </p>
</div>

<?php echo $__env->renderComponent(); ?><?php /**PATH C:\Projet\qualywatch\backend\resources\views\emails\feedback-notification.blade.php ENDPATH**/ ?>