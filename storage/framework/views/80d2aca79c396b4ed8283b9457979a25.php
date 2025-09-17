<?php $__env->startComponent('emails.layout'); ?>
    <?php $__env->slot('title', 'Validation de votre feedback'); ?>
    <?php $__env->slot('company_name', $company_name); ?>
    <?php $__env->slot('header_subtitle', ''); ?>
    <?php $__env->slot('reference', $feedback_reference); ?>

    <p>Bonjour <strong><?php echo e($client_name); ?></strong>,</p>

    <p>Nous avons traité votre <?php echo e($feedback_type); ?> et souhaitons connaître votre avis sur notre action.</p>

    <div class="info-card">
        <div style="display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; text-transform: uppercase; color: white; background: linear-gradient(135deg, #EA580C 0%, #FB923C 100%); margin-bottom: 15px;">
            <?php echo e($feedback_type === 'incident' ? 'Incident' : 'Suggestion'); ?>

        </div>
        
        <div style="margin-bottom: 10px;">
            <strong>Référence:</strong> <?php echo e($feedback_reference); ?>

        </div>
        <div style="margin-bottom: 15px;">
            <strong>Titre:</strong> <?php echo e($feedback_title); ?>

        </div>

        <div style="background: #FEF3E2; border: 1px solid #FDBA74; border-radius: 8px; padding: 15px; margin: 15px 0;">
            <h4 style="margin: 0 0 10px 0; color: #EA580C;">Action entreprise:</h4>
            <p style="margin: 0;"><?php echo e($admin_resolution); ?></p>
        </div>
    </div>

    <div style="background: #FEF3E2; border: 1px solid #FDBA74; border-radius: 6px; padding: 15px; margin: 20px 0; color: #C2410C;">
        <strong>Important:</strong> Cette validation expire le <?php echo e($expires_at); ?>.
    </div>

    <div class="text-center mt-20 mb-20">
        <p><strong>Merci de nous indiquer si notre intervention a résolu votre problème :</strong></p>
        
        <div style="margin: 30px 0;">
            <table cellpadding="0" cellspacing="0" style="margin: 0 auto;">
                <tr>
                    <td style="padding: 10px;">
                        <a href="<?php echo e($resolved_url); ?>" style="display: inline-block; background: linear-gradient(135deg, #10B981 0%, #34D399 100%); color: white; padding: 15px 30px; text-decoration: none; border-radius: 25px; font-weight: bold; font-size: 16px; min-width: 150px; text-align: center;">
                            ✅ Problème résolu
                        </a>
                    </td>
                    <td style="padding: 10px;">
                        <a href="<?php echo e($not_resolved_url); ?>" style="display: inline-block; background: linear-gradient(135deg, #EF4444 0%, #F87171 100%); color: white; padding: 15px 30px; text-decoration: none; border-radius: 25px; font-weight: bold; font-size: 16px; min-width: 150px; text-align: center;">
                            ❌ Non résolu
                        </a>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <div style="margin-top: 30px;">
        <strong>Pourquoi valider ?</strong><br>
        • Nous aider à améliorer nos services<br>
        • Confirmer que votre problème est résolu<br>
        • Gagner des KaliPoints bonus selon votre satisfaction<br>
        • Participer à l'amélioration de l'expérience client
    </div>

    <p style="font-size: 14px; color: #666; margin-top: 20px;">
        Si les boutons ne fonctionnent pas, vous pouvez aussi nous contacter directement.
    </p>

<?php echo $__env->renderComponent(); ?><?php /**PATH C:\Projet\qualywatch\backend\resources\views/emails/feedback-validation-v2.blade.php ENDPATH**/ ?>