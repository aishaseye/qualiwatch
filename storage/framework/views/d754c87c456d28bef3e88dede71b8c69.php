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

<div style="text-align: center; margin-bottom: 30px;">
    <?php if($isNegative): ?>
    <div style="background: linear-gradient(135deg, #FED7D7 0%, #FC8181 100%); padding: 20px; border-radius: 12px;">
        <h1 style="color: #742A2A; margin: 0; font-size: 24px;">
            🚨 Feedback Négatif Reçu
        </h1>
        <p style="color: #C53030; margin: 5px 0 0 0; font-size: 14px; font-weight: 500;">
            Action immédiate requise
        </p>
    </div>
    <?php else: ?>
    <div style="background: linear-gradient(135deg, #E6FFFA 0%, #81E6D9 100%); padding: 20px; border-radius: 12px;">
        <h1 style="color: #234E52; margin: 0; font-size: 24px;">
            📝 Nouveau Feedback Reçu
        </h1>
    </div>
    <?php endif; ?>
</div>


<p style="font-size: 16px; color: #4A5568;">
    Bonjour **<?php echo e($company->name); ?>**,
</p>

<?php if($isNegative): ?>
<div style="background: #FFF5F5; border-left: 4px solid #F56565; padding: 15px; margin: 20px 0; border-radius: 4px;">
    <p style="color: #C53030; margin: 0; font-weight: 500;">
        ⚠️ Un client a exprimé son mécontentement. Une réponse rapide est recommandée.
    </p>
</div>
<?php endif; ?>


<div style="text-align: center; margin: 30px 0;">
    <div style="background: white; border: 3px solid <?php echo e($urgencyLevel['color']); ?>; border-radius: 16px; padding: 25px; box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1); max-width: 320px; margin: 0 auto;">
        <h3 style="color: #4A5568; margin: 0 0 15px 0; font-size: 16px; font-weight: 500;">Évaluation Client</h3>
        
        <div style="font-size: 36px; margin: 15px 0;"><?php echo e($ratingStars); ?></div>
        
        <div style="font-size: 28px; font-weight: bold; color: <?php echo e($urgencyLevel['color']); ?>; margin: 10px 0;">
            <?php echo e($feedback->rating); ?>/5
        </div>
        
        <div style="background: <?php echo e($urgencyLevel['color']); ?>; color: white; padding: 6px 16px; border-radius: 25px; font-size: 12px; font-weight: bold; display: inline-block; margin-top: 10px;">
            <?php echo e($urgencyLevel['level']); ?>

        </div>
    </div>
</div>


<div style="background: #F7FAFC; padding: 20px; border-radius: 8px; margin: 25px 0;">
    <h3 style="color: #2D3748; margin: 0 0 15px 0; font-size: 18px;">📊 Détails du Feedback</h3>
    
    <div style="margin-bottom: 10px;">
        <strong style="color: #4A5568;">Client:</strong> <?php echo e($client->full_name ?? 'Client Anonyme'); ?>

    </div>
    <div style="margin-bottom: 10px;">
        <strong style="color: #4A5568;">Type:</strong> <?php echo e($feedbackType); ?>

    </div>
    <div style="margin-bottom: 10px;">
        <strong style="color: #4A5568;">Date:</strong> <?php echo e($feedback->created_at->format('d/m/Y à H:i')); ?>

    </div>
    
    <?php if($feedback->service): ?>
    <div style="margin-bottom: 10px;">
        <strong style="color: #4A5568;">Service:</strong> 
        <span style="color: <?php echo e($feedback->service->color); ?>; font-weight: bold;">
            <?php echo e($feedback->service->name); ?>

        </span>
    </div>
    <?php endif; ?>
    
    <?php if($feedback->employee): ?>
    <div style="margin-bottom: 10px;">
        <strong style="color: #4A5568;">Employé:</strong> <?php echo e($feedback->employee->full_name); ?>

    </div>
    <?php endif; ?>
</div>


<?php if($feedback->message): ?>
<div style="background: #EDF2F7; padding: 20px; border-radius: 8px; margin: 25px 0;">
    <h4 style="color: #4A5568; margin: 0 0 10px 0; font-size: 16px;">💬 Message du Client</h4>
    <p style="font-style: italic; color: #2D3748; margin: 0; font-size: 15px; line-height: 1.5; padding: 10px; background: white; border-radius: 4px;">
        "<?php echo e($feedback->message); ?>"
    </p>
</div>
<?php endif; ?>


<?php if($isNegative): ?>
<div style="background: linear-gradient(135deg, #FED7D7 0%, #FEB2B2 100%); padding: 20px; border-radius: 8px; margin: 25px 0;">
    <h3 style="color: #742A2A; margin: 0 0 15px 0; font-size: 18px; text-align: center;">
        ⚡ Actions Prioritaires
    </h3>
    <div style="text-align: center;">
        <p style="color: #C53030; margin: 5px 0; font-size: 14px; font-weight: 500;">
            📞 Contacter le client dans les plus brefs délais
        </p>
        <p style="color: #C53030; margin: 5px 0; font-size: 14px; font-weight: 500;">
            🔍 Identifier et corriger le problème
        </p>
        <p style="color: #C53030; margin: 5px 0; font-size: 14px; font-weight: 500;">
            📝 Documenter les actions prises
        </p>
    </div>
</div>

<?php if (isset($component)) { $__componentOriginal15a5e11357468b3880ae1300c3be6c4f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal15a5e11357468b3880ae1300c3be6c4f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => $__env->getContainer()->make(Illuminate\View\Factory::class)->make('mail::button'),'data' => ['url' => config('app.frontend_url', config('app.url')) . '/dashboard/feedbacks/' . $feedback->id,'color' => 'error']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('mail::button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['url' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(config('app.frontend_url', config('app.url')) . '/dashboard/feedbacks/' . $feedback->id),'color' => 'error']); ?>
🚨 Traiter ce Feedback Urgent
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
<?php else: ?>
<?php if (isset($component)) { $__componentOriginal15a5e11357468b3880ae1300c3be6c4f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal15a5e11357468b3880ae1300c3be6c4f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => $__env->getContainer()->make(Illuminate\View\Factory::class)->make('mail::button'),'data' => ['url' => config('app.frontend_url', config('app.url')) . '/dashboard/feedbacks/' . $feedback->id,'color' => 'primary']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('mail::button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['url' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(config('app.frontend_url', config('app.url')) . '/dashboard/feedbacks/' . $feedback->id),'color' => 'primary']); ?>
📝 Voir le Feedback
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
<?php endif; ?>


<div style="background: #EDF2F7; padding: 15px; border-radius: 8px; margin: 25px 0; text-align: center;">
    <h4 style="color: #4A5568; margin: 0 0 10px 0; font-size: 14px;">📈 Vos Statistiques</h4>
    <p style="margin: 5px 0; color: #2D3748; font-size: 13px;">
        <strong>Satisfaction moyenne:</strong> <?php echo e(number_format($company->satisfaction_score, 1)); ?>/5 ⭐
    </p>
    <p style="margin: 5px 0; color: #2D3748; font-size: 13px;">
        <strong>Total feedbacks:</strong> <?php echo e($company->total_feedbacks); ?>

    </p>
</div>


<div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #E2E8F0;">
    <p style="color: #718096; font-size: 12px; margin: 0;">
        Cet email a été envoyé automatiquement par Qualy<?php echo e($company->name); ?>

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
<?php endif; ?><?php /**PATH C:\Projet\qualywatch\backend\resources\views\emails\feedback-notification-simple.blade.php ENDPATH**/ ?>