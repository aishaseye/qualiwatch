<?php $__env->startComponent('emails.layout'); ?>
    <?php $__env->slot('title', 'Merci pour votre suggestion'); ?>
    <?php $__env->slot('company_name', $company->name); ?>
    <?php $__env->slot('header_subtitle', ''); ?>

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

        <?php if($message): ?>
        <div style="background: #EFF6FF; border: 1px solid #BFDBFE; border-radius: 8px; padding: 15px; margin: 15px 0;">
            <h4 style="margin: 0 0 10px 0; color: #2563EB;">Votre suggestion:</h4>
            <p style="margin: 0; font-style: italic;">"<?php echo e($message); ?>"</p>
        </div>
        <?php endif; ?>
    </div>

    <div style="background: linear-gradient(135deg, #DBEAFE 0%, #BFDBFE 100%); border-radius: 8px; padding: 20px; margin: 25px 0; text-align: center;">
        <h3 style="color: #1E40AF; margin: 0 0 15px 0;">🙏 Votre avis compte pour nous</h3>
        <p style="margin: 0; color: #1E3A8A;">
            Vos suggestions nous aident à améliorer continuellement nos services.<br>
            Notre équipe va étudier attentivement votre proposition.
        </p>
    </div>

    <div style="margin-top: 30px;">
        <strong>Que se passe-t-il ensuite ?</strong><br>
        • Notre équipe analyse votre suggestion<br>
        • Nous évaluons la faisabilité de sa mise en œuvre<br>
        • Vous serez informé(e) si nous décidons de l'implémenter<br>
        • Votre contribution sera reconnue le cas échéant
    </div>

    <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 8px; padding: 15px; margin: 20px 0; text-align: center;">
        <p style="margin: 0; font-size: 14px; color: #64748B;">
            💝 <strong>Bonus:</strong> Vous gagnez des KaliPoints pour cette suggestion constructive !
        </p>
    </div>

    <p style="font-size: 14px; color: #666; margin-top: 20px;">
        N'hésitez pas à nous faire d'autres suggestions. Ensemble, nous construisons une meilleure expérience !
    </p>

<?php echo $__env->renderComponent(); ?><?php /**PATH C:\Projet\qualywatch\backend\resources\views\emails\suggestion-thank-you.blade.php ENDPATH**/ ?>