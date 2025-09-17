<?php $__env->startComponent('emails.layout'); ?>
    <?php $__env->slot('title', '💡 Nouvelle Suggestion Reçue'); ?>
    <?php $__env->slot('company_name', $company->name ?? 'QualyWatch'); ?>
    <?php $__env->slot('header_subtitle', 'Idée d\'amélioration'); ?>
    <?php $__env->slot('reference', $feedback->reference ?? $feedback->id ?? ''); ?>

    <p>Bonjour <strong><?php echo e($company->name ?? 'Manager'); ?></strong>,</p>

    <div style="background: linear-gradient(135deg, #DBEAFE 0%, #BFDBFE 100%); border: 1px solid #93C5FD; border-radius: 8px; padding: 20px; margin: 20px 0;">
        <div style="display: flex; align-items: center; margin-bottom: 15px;">
            <span style="font-size: 24px; margin-right: 15px;">💡</span>
            <div>
                <h3 style="margin: 0; color: #1D4ED8;">Nouvelle Suggestion Reçue</h3>
                <p style="margin: 0; color: #1E40AF; font-size: 14px;">Un client a partagé une idée d'amélioration avec vous.</p>
            </div>
        </div>
    </div>

    <div class="info-card">
        <div style="display: inline-block; padding: 6px 16px; border-radius: 25px; font-size: 12px; font-weight: bold; text-transform: uppercase; color: white; background: linear-gradient(135deg, #2563EB 0%, #3B82F6 100%); margin-bottom: 15px;">
            💡 Suggestion
        </div>

        <div style="text-align: center; margin-bottom: 20px;">
            <div style="background: white; padding: 15px; border-radius: 8px; display: inline-block; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); border: 2px solid #2563EB;">
                <div style="font-size: 28px; margin-bottom: 5px;"><?php echo e($ratingStars ?? str_repeat('⭐', $feedback->rating ?? 5)); ?></div>
                <div style="font-size: 20px; font-weight: bold; color: #2563EB;">
                    <?php echo e($feedback->rating ?? 'N/A'); ?>/5
                </div>
                <div style="font-size: 12px; color: #2563EB; margin-top: 5px; font-weight: bold;">
                    CONSTRUCTIF
                </div>
            </div>
        </div>

        <div style="margin-bottom: 10px;">
            <strong>Client:</strong> <?php echo e($client->full_name ?? 'Client Anonyme'); ?><br>
            <strong>Email:</strong> <?php echo e($client->email ?? 'Non renseigné'); ?><br>
            <strong>Type:</strong> Suggestion d'amélioration<br>
            <strong>Date:</strong> <?php echo e($feedback->created_at->format('d/m/Y à H:i') ?? now()->format('d/m/Y à H:i')); ?>

        </div>

        <?php if($feedback->service ?? false): ?>
        <div style="margin-bottom: 10px;">
            <strong>Service concerné:</strong> <?php echo e($feedback->service->name ?? 'Non spécifié'); ?>

        </div>
        <?php endif; ?>

        <?php if($feedback->employee ?? false): ?>
        <div style="margin-bottom: 10px;">
            <strong>Employé concerné:</strong> <?php echo e($feedback->employee->full_name ?? 'Non spécifié'); ?>

        </div>
        <?php endif; ?>
    </div>

    <div style="margin-top: 20px;">
        <strong>Suggestion du Client :</strong>
        <div style="background: #EFF6FF; border-left: 4px solid #2563EB; border-radius: 8px; padding: 15px; margin-top: 10px;">
            <p style="margin: 0; font-style: italic; color: #374151;">
                "<?php echo e($feedback->description ?? $feedback->message ?? 'Aucun message fourni'); ?>"
            </p>
        </div>
    </div>

    <div style="background: linear-gradient(135deg, #DBEAFE 0%, #BFDBFE 100%); border-radius: 8px; padding: 20px; margin: 25px 0;">
        <h3 style="color: #1D4ED8; margin: 0 0 15px 0;">💡 Actions Recommandées</h3>
        <div style="color: #1E40AF; font-weight: bold; margin-bottom: 15px;">Évaluation et implémentation :</div>

        <ol style="color: #333; line-height: 1.8; margin: 0; padding-left: 20px;">
            <li><strong>Analysez la suggestion</strong> et sa faisabilité</li>
            <li><strong>Évaluez l'impact</strong> sur l'expérience client</li>
            <li><strong>Planifiez l'implémentation</strong> si pertinente</li>
            <li><strong>Remerciez le client</strong> pour sa contribution</li>
        </ol>
    </div>

    <div style="text-align: center; margin: 30px 0;">
        <a href="<?php echo e(config('app.frontend_url', config('app.url')) . '/dashboard/feedbacks/' . $feedback->id); ?>"
           style="display: inline-block; background: linear-gradient(135deg, #2563EB 0%, #3B82F6 100%); color: white; padding: 15px 30px; text-decoration: none; border-radius: 25px; font-weight: bold; font-size: 16px;">
            💡 Examiner cette Suggestion
        </a>
    </div>

    <div style="margin-top: 30px;">
        <strong>Statistiques Rapides :</strong><br>
        • Satisfaction générale : <?php echo e(number_format($company->satisfaction_score ?? 4.2, 1)); ?>/5 ⭐<br>
        • Total feedbacks : <?php echo e($company->total_feedbacks ?? 'N/A'); ?> retours reçus<br>
        • Suggestions ce mois : <?php echo e($company->suggestions_count ?? 'N/A'); ?><br>
        • Taux d'implémentation : 78%
    </div>

    <div style="background-color: #EFF6FF; padding: 15px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #2563EB;">
        <strong style="color: #1D4ED8;">💡 Conseil</strong><br>
        <span style="color: #1E40AF;">
            Les suggestions clients sont précieuses pour l'amélioration continue. Même si non implémentées, elles montrent l'engagement de vos clients envers votre entreprise.
        </span>
    </div>

<?php echo $__env->renderComponent(); ?><?php /**PATH C:\Projet\qualywatch\backend\resources\views/emails/feedback-notification-suggestion.blade.php ENDPATH**/ ?>