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
    <img src="<?php echo e(asset('images/qualywatch-logo.png')); ?>" alt="Qualy<?php echo e($company->name); ?>" style="max-width: 200px; height: auto;">
</div>

# <?php echo e($apologyLevel['title']); ?>


Bonjour <?php echo e($clientName); ?>,

Nous avons pris connaissance de votre retour concernant votre récente expérience chez **<?php echo e($company->name); ?>**. 

<?php echo e($apologyLevel['message']); ?>


## 📝 Votre Évaluation

<?php if (isset($component)) { $__componentOriginal91214b38020aa1d764d4a21e693f703c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal91214b38020aa1d764d4a21e693f703c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => $__env->getContainer()->make(Illuminate\View\Factory::class)->make('mail::panel'),'data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('mail::panel'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<div style="background: linear-gradient(135deg, <?php echo e($apologyLevel['color']); ?>20 0%, <?php echo e($apologyLevel['color']); ?>40 100%); padding: 25px; border-radius: 12px; text-align: center; border: 2px solid <?php echo e($apologyLevel['color']); ?>80; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
    <div style="background: white; padding: 20px; border-radius: 8px; display: inline-block; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); border: 2px solid <?php echo e($apologyLevel['color']); ?>;">
        <h3 style="margin: 0 0 10px 0; color: #374151; font-size: 18px;">Votre Note</h3>
        <div style="font-size: 32px; line-height: 1.2; margin: 10px 0;">
            <?php echo e($ratingStars); ?>

        </div>
        <div style="font-size: 24px; font-weight: bold; color: <?php echo e($apologyLevel['color']); ?>; margin-top: 8px;">
            <?php echo e($feedback->rating); ?>/5
        </div>
        <div style="font-size: 12px; color: <?php echo e($apologyLevel['color']); ?>; margin-top: 8px; font-weight: bold;">
            PRIORITÉ <?php echo e($apologyLevel['intensity']); ?>

        </div>
    </div>
</div>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal91214b38020aa1d764d4a21e693f703c)): ?>
<?php $attributes = $__attributesOriginal91214b38020aa1d764d4a21e693f703c; ?>
<?php unset($__attributesOriginal91214b38020aa1d764d4a21e693f703c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal91214b38020aa1d764d4a21e693f703c)): ?>
<?php $component = $__componentOriginal91214b38020aa1d764d4a21e693f703c; ?>
<?php unset($__componentOriginal91214b38020aa1d764d4a21e693f703c); ?>
<?php endif; ?>

## 💬 Votre Message

<?php if (isset($component)) { $__componentOriginal91214b38020aa1d764d4a21e693f703c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal91214b38020aa1d764d4a21e693f703c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => $__env->getContainer()->make(Illuminate\View\Factory::class)->make('mail::panel'),'data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('mail::panel'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<div style="background-color: #F9FAFB; padding: 20px; border-radius: 8px; border-left: 4px solid #6B7280;">
    <em style="font-size: 16px; line-height: 1.5; color: #4B5563;">
    "<?php echo e($feedback->message); ?>"
    </em>
</div>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal91214b38020aa1d764d4a21e693f703c)): ?>
<?php $attributes = $__attributesOriginal91214b38020aa1d764d4a21e693f703c; ?>
<?php unset($__attributesOriginal91214b38020aa1d764d4a21e693f703c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal91214b38020aa1d764d4a21e693f703c)): ?>
<?php $component = $__componentOriginal91214b38020aa1d764d4a21e693f703c; ?>
<?php unset($__componentOriginal91214b38020aa1d764d4a21e693f703c); ?>
<?php endif; ?>

## 🙏 Notre Engagement

Nous comprenons votre déception et nous prenons votre retour très au sérieux. Voici notre engagement :

<div style="background: linear-gradient(135deg, #DBEAFE 0%, #BFDBFE 100%); padding: 20px; border-radius: 8px; margin: 20px 0;">
    <ul style="list-style: none; padding: 0; margin: 0;">
        <li style="margin-bottom: 10px; color: #1E40AF;">
            <strong>🔍 Analyse approfondie</strong> - Nous identifions les causes du problème
        </li>
        <li style="margin-bottom: 10px; color: #1E40AF;">
            <strong>⚡ Actions correctives</strong> - Nous mettons en place des améliorations immédiates
        </li>
        <li style="margin-bottom: 10px; color: #1E40AF;">
            <strong>📞 Suivi personnalisé</strong> - <?php echo e($apologyLevel['urgency']); ?>

        </li>
        <li style="color: #1E40AF;">
            <strong>🎯 Prévention</strong> - Nous évitons que cela se reproduise
        </li>
    </ul>
</div>

## 💝 Geste Commercial

Pour vous témoigner de notre attachement à votre satisfaction, nous souhaitons vous offrir un **geste commercial** lors de votre prochaine visite.

<?php if (isset($component)) { $__componentOriginal15a5e11357468b3880ae1300c3be6c4f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal15a5e11357468b3880ae1300c3be6c4f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => $__env->getContainer()->make(Illuminate\View\Factory::class)->make('mail::button'),'data' => ['url' => 'tel:' . ($company->phone ?? ''),'color' => 'success']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('mail::button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['url' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('tel:' . ($company->phone ?? '')),'color' => 'success']); ?>
📞 Nous Contacter Directement
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

---

<div style="background-color: #FEF3C7; padding: 20px; border-radius: 8px; text-align: center; margin: 25px 0;">
    <h4 style="margin: 0 0 10px 0; color: #D97706;">🌟 Votre Satisfaction est Notre Priorité</h4>
    <p style="margin: 0; color: #92400E; font-style: italic;">
        Chaque retour nous aide à nous améliorer. Merci de nous donner l'opportunité de vous offrir une meilleure expérience.
    </p>
</div>

**Qualy<?php echo e($company->name); ?>** - Nous nous excusons et nous engageons à mieux faire  
*"L'excellence naît de l'amélioration continue"*

<div style="text-align: center; margin-top: 30px; color: #6B7280; font-size: 12px;">
    <p>Si vous avez des questions, n'hésitez pas à nous contacter</p>
    <p><strong>Téléphone:</strong> <?php echo e($company->phone ?? 'Non disponible'); ?></p>
    <p><strong>Email:</strong> <?php echo e($company->email ?? 'Non disponible'); ?></p>
</div>

Avec nos excuses renouvelées,<br>
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
<?php endif; ?><?php /**PATH C:\Projet\qualywatch\backend\resources\views\emails\client-apology.blade.php ENDPATH**/ ?>