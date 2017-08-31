<?php $__env->startSection('title'); ?>
    <?php echo e(trans('book.title', ['page' => $page['current']])); ?> - ##parent-placeholder-3c6de1b7dd91465d437ef415f94f36afc1fbc8a8##
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

    <h1><?php echo e(trans('book.header')); ?></h1>

    <a href="/rules">Правила</a> /
    <a href="/smiles">Смайлы</a> /
    <a href="/tags">Теги</a>

    <?php if (is_admin()):?>
        / <a href="/admin/book?page=<?=$page['current']?>">Управление</a>
    <?php endif;?>
    <hr>

    <?php if ($page['total'] > 0): ?>
        <?php foreach ($posts as $data): ?>

            <div class="post">
                <div class="b">

                    <?php if (is_user() && getUserId() != $data['user_id']): ?>

                        <div class="float-right">
                            <a href="#" onclick="return postReply(this)" title="Ответить"><i class="fa fa-reply text-muted"></i></a>
                            <a href="#" onclick="return postQuote(this)" title="Цитировать"><i class="fa fa-quote-right text-muted"></i></a>

                            <a href="#" onclick="return sendComplaint(this)" data-type="<?php echo e(Guest::class); ?>" data-id="<?php echo e($data['id']); ?>" data-token="<?php echo e($_SESSION['token']); ?>" data-page="<?php echo e($page['current']); ?>" rel="nofollow" title="Жалоба"><i class="fa fa-bell text-muted"></i></a>
                        </div>

                    <?php endif; ?>

                    <?php if (getUserId() == $data['user_id'] && $data['created_at'] + 600 > SITETIME): ?>
                        <div class="float-right">
                            <a href="/book/edit/<?=$data['id']?>" title="Редактировать"><i class="fa fa-pencil text-muted"></i></a>
                        </div>
                    <?php endif; ?>

                    <div class="img"><?=userAvatar($data->user)?></div>

                    <?php if (empty($data['user_id'])): ?>
                        <b><?= setting('guestsuser') ?></b> <small>(<?=date_fixed($data['created_at'])?>)</small>
                    <?php else: ?>
                        <b><?=profile($data->user)?></b> <small>(<?=date_fixed($data['created_at'])?>)</small><br>
                        <?=user_title($data->user)?> <?=user_online($data->user)?>
                    <?php endif; ?>
                </div>

                <div class="message"><?=bbCode($data['text'])?></div>

                <?php if (!empty($data['edit_user_id'])): ?>
                    <small><i class="fa fa-exclamation-circle text-danger"></i> Отредактировано: <?= $data->getEditUser()->login ?> (<?=date_fixed($data['updated_at'])?>)</small><br>
                <?php endif; ?>

                <?php if (is_admin()): ?>
                    <span class="data">(<?=$data['brow']?>, <?=$data['ip']?>)</span>
                <?php endif; ?>

                <?php if (!empty($data['reply'])): ?>
                    <br><span style="color:#ff0000">Ответ: <?=bbCode($data['reply'])?></span>
                <?php endif; ?>

            </div>
        <?php endforeach; ?>

        <?php pagination($page) ?>

    <?php else: ?>
        <?php showError('Сообщений нет, будь первым!'); ?>
    <?php endif; ?>


    <?php if (is_user()): ?>
        <div class="form">
            <form action="book/add" method="post">
                <input type="hidden" name="token" value="<?= $_SESSION['token'] ?>">
                <div class="form-group<?php echo e(hasError('msg')); ?>">
                    <label for="markItUp">Сообщение:</label>
                    <textarea class="form-control" id="markItUp" rows="5" name="msg" placeholder="Текст сообщения" required><?php echo e(getInput('msg')); ?></textarea>
                    <?php echo textError('msg'); ?>

                </div>

                <button class="btn btn-primary">Написать</button>
            </form>
        </div><br>

    <?php elseif (setting('bookadds') == 1): ?>

        <div class="form">
            <form action="book/add" method="post">
                <input type="hidden" name="token" value="<?= $_SESSION['token'] ?>">

                <div class="form-group<?php echo e(hasError('msg')); ?>">
                    <label for="inputText">Сообщение:</label>
                    <textarea class="form-control" id="inputText" rows="5" name="msg" placeholder="Текст сообщения" required><?php echo e(getInput('msg')); ?></textarea>
                    <?php echo textError('msg'); ?>

                </div>

                <div class="form-group<?php echo e(hasError('protect')); ?>">
                    <label for="inputProtect">Проверочный код:</label>
                    <img src="/captcha" id="captcha" onclick="this.src='/captcha?'+Math.random()" class="rounded" alt="" style="cursor: pointer;">
                    <input class="form-control" name="protect" id="inputProtect" maxlength="6" required>
                    <?php echo textError('protect'); ?>

                </div>

                <button class="btn btn-primary">Написать</button>
            </form>
        </div><br>

    <?php else: ?>
        <?php showError('Для добавления сообщения необходимо авторизоваться'); ?>
    <?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layout', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>