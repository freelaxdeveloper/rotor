<?php $__env->startSection('title'); ?>
    Ошибка 403 - ##parent-placeholder-3c6de1b7dd91465d437ef415f94f36afc1fbc8a8##
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

    <?php $images = glob(HOME.'/assets/img/errors/*.png'); ?>

    <div class="row">
        <div class="col-md-4 text-center">
            <img src="/assets/img/errors/<?php echo e(basename($images[array_rand($images)])); ?>" alt="error 403">
        </div>
        <div class="col-md-8">
            <h3>Ошибка 403!</h3>

            <?php if($message): ?>
                <div class="lead"><?php echo e($message); ?></div>
            <?php else: ?>
                <div class="lead">Доступ запрещен!</div>
            <?php endif; ?>

            <?php if($referer): ?>
                <div style="position: absolute; bottom: 0;">
                    <i class="fa fa-arrow-circle-left"></i> <a href="<?php echo e($referer); ?>">Вернуться</a><br>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layout', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>