<?php if (isset($component)) { $__componentOriginalaa758e6a82983efcbf593f765e026bd9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalaa758e6a82983efcbf593f765e026bd9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => $__env->getContainer()->make(Illuminate\View\Factory::class)->make('mail::message'),'data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('mail::message'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>

<div style="text-align: center; margin-bottom: 40px;">
    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; border-radius: 12px;">
        <h1 style="color: white; margin: 0; font-size: 24px; font-weight: 300;">
            🙏 <?php echo e($apologyLevel['title']); ?>

        </h1>
    </div>
</div>


<div style="text-align: center; margin-bottom: 30px;">
    <p style="font-size: 18px; color: #4A5568; margin: 0;">
        Bonjour <strong><?php echo e($clientName); ?></strong>,
    </p>
</div>


<div style="background: #F7FAFC; padding: 25px; border-radius: 8px; margin-bottom: 30px; border-left: 4px solid <?php echo e($apologyLevel['color']); ?>;">
    <p style="font-size: 16px; line-height: 1.6; color: #2D3748; margin: 0;">
        <?php echo e($apologyLevel['message']); ?>

    </p>
</div>


<div style="text-align: center; margin: 30px 0;">
    <div style="background: white; border: 2px solid <?php echo e($apologyLevel['color']); ?>; border-radius: 12px; padding: 25px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); max-width: 280px; margin: 0 auto;">
        <h3 style="color: #4A5568; margin: 0 0 15px 0; font-size: 16px; font-weight: 500;">Votre Évaluation</h3>
        
        <div style="font-size: 28px; margin: 10px 0;"><?php echo e($ratingStars); ?></div>
        
        <div style="font-size: 32px; font-weight: bold; color: <?php echo e($apologyLevel['color']); ?>; margin: 8px 0;">
            <?php echo e($feedback->rating); ?>/5
        </div>
        
        <div style="background: <?php echo e($apologyLevel['color']); ?>; color: white; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: bold; display: inline-block; margin-top: 8px;">
            <?php echo e($apologyLevel['intensity']); ?>

        </div>
    </div>
</div>


<?php if($feedback->message): ?>
<div style="background: #EDF2F7; padding: 20px; border-radius: 8px; margin: 25px 0;">
    <h4 style="color: #4A5568; margin: 0 0 10px 0; font-size: 14px; text-transform: uppercase; letter-spacing: 1px;">Votre Message</h4>
    <p style="font-style: italic; color: #2D3748; margin: 0; font-size: 15px; line-height: 1.5;">
        "<?php echo e($feedback->message); ?>"
    </p>
</div>
<?php endif; ?>


<div style="background: linear-gradient(135deg, #E6FFFA 0%, #B2F5EA 100%); padding: 25px; border-radius: 8px; margin: 25px 0;">
    <h3 style="color: #234E52; margin: 0 0 15px 0; font-size: 18px; text-align: center;">
        🤝 Notre Engagement
    </h3>
    
    <div style="text-align: center;">
        <div style="display: inline-block; text-align: left; max-width: 400px;">
            <p style="color: #2C7A7B; margin: 8px 0; font-size: 14px;">
                ✅ <strong>Analyse immédiate</strong> du problème signalé
            </p>
            <p style="color: #2C7A7B; margin: 8px 0; font-size: 14px;">
                ⚡ <strong><?php echo e($apologyLevel['urgency']); ?></strong>
            </p>
            <p style="color: #2C7A7B; margin: 8px 0; font-size: 14px;">
                🎯 <strong>Actions correctives</strong> pour éviter toute récidive
            </p>
        </div>
    </div>
</div>


<div style="text-align: center; margin: 30px 0;">
    <div style="background: #FFF5F5; border: 2px dashed #FC8181; padding: 20px; border-radius: 8px;">
        <h4 style="color: #C53030; margin: 0 0 10px 0; font-size: 16px;">
            🎁 Geste Commercial
        </h4>
        <p style="color: #744210; margin: 0; font-size: 14px;">
            Un avantage vous sera proposé lors de votre prochaine visite
        </p>
    </div>
</div>


<?php if (isset($component)) { $__componentOriginal15a5e11357468b3880ae1300c3be6c4f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal15a5e11357468b3880ae1300c3be6c4f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => $__env->getContainer()->make(Illuminate\View\Factory::class)->make('mail::button'),'data' => ['url' => 'tel:' . ($company->phone ?? ''),'color' => 'primary']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('mail::button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['url' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('tel:' . ($company->phone ?? '')),'color' => 'primary']); ?>
📞 Nous Contacter : <?php echo e($company->phone ?? 'Bientôt disponible'); ?>

 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal15a5e11357468b3880ae1300c3be6c4f)): ?>
<?php $attributes = $__attributesOriginal15a5e11357468b3880ae1300c3be6c4f; ?>
<?php unset($__attributesOriginal15a5e11357468b3880ae1300c3be6c4f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal15a5e11357468b3880ae1300c3be6c4f)): ?>
<?php $component = $__componentOriginal15a5e11357468b3880ae1300c3be6c4f; ?>
<?php unset($__componentOriginal15a5e11357468b3880ae1300c3be6c4f); ?>
<?php endif; ?>


<div style="text-align: center; margin-top: 40px; padding-top: 20px; border-top: 1px solid #E2E8F0;">
    <p style="color: #718096; font-size: 12px; margin: 5px 0;">
        <strong>Qualy<?php echo e($company->name); ?></strong> - Nous nous excusons sincèrement
    </p>
    <p style="color: #A0AEC0; font-size: 11px; margin: 0;">
        Cet email a été envoyé automatiquement suite à votre feedback
    </p>
</div>

Cordialement,<br>
L'équipe Qualy<?php echo e($company->name); ?>

 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalaa758e6a82983efcbf593f765e026bd9)): ?>
<?php $attributes = $__attributesOriginalaa758e6a82983efcbf593f765e026bd9; ?>
<?php unset($__attributesOriginalaa758e6a82983efcbf593f765e026bd9); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalaa758e6a82983efcbf593f765e026bd9)): ?>
<?php $component = $__componentOriginalaa758e6a82983efcbf593f765e026bd9; ?>
<?php unset($__componentOriginalaa758e6a82983efcbf593f765e026bd9); ?>
<?php endif; ?><?php /**PATH C:\Projet\qualywatch\backend\resources\views\emails\client-apology-simple.blade.php ENDPATH**/ ?>