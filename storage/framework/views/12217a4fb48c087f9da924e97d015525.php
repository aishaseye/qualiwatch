<?php $__env->startComponent('emails.layout', [
    'title' => 'Vérification Email - QualyWatch',
    'company_name' => 'Watch',
    'header_subtitle' => 'Vérification de votre adresse email',
    'reference' => 'OTP-' . now()->format('Ymd')
]); ?>

<div class="text-center mb-20">
    <h2 style="color: #333; margin-bottom: 10px;">Bonjour <?php echo e($userName); ?> 👋</h2>
    <p style="color: #666; font-size: 16px;">Bienvenue sur <strong>Qualy</strong> ! Pour finaliser votre inscription, veuillez utiliser le code de vérification ci-dessous :</p>
</div>

<div class="info-card text-center">
    <div class="highlight-number" style="font-size: 48px; letter-spacing: 8px; margin: 20px 0;">
        <?php echo e($otp); ?>

    </div>
</div>

<div class="info-card">
    <h3 style="color: #EA580C; margin-top: 0;">⚠️ Instructions importantes :</h3>
    <ul style="color: #333; line-height: 1.8;">
        <li>Ce code est valide pendant <strong>10 minutes</strong> (jusqu'à <?php echo e($expiresAt); ?>)</li>
        <li>Ne partagez <strong>jamais</strong> ce code avec quelqu'un d'autre</li>
        <li>Si vous n'avez pas demandé cette vérification, ignorez cet email</li>
    </ul>
</div>

<div class="text-center mt-20">
    <a href="<?php echo e(config('app.frontend_url', config('app.url')) . '/verify-otp'); ?>" class="btn-orange">
        ✅ Vérifier mon email
    </a>
</div>

<div class="text-center mt-20">
    <p style="color: #666; font-size: 14px; margin-bottom: 10px;">
        <strong>Qualy</strong> - Votre solution de gestion des retours clients
    </p>
    <p style="color: #999; font-size: 12px;">
        Transformez chaque feedback en opportunité d'amélioration
    </p>
</div>

<?php echo $__env->renderComponent(); ?>
<?php /**PATH C:\Projet\qualywatch\backend\resources\views\emails\otp-verification.blade.php ENDPATH**/ ?>