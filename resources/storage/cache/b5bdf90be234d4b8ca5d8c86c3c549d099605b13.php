<?php
header('Content-type:text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <title>
        <?php $__env->startSection('title'); ?>
            <?php echo e(setting('title')); ?>

        <?php echo $__env->yieldSection(); ?>
    </title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="icon" href="/favicon.ico">
    <link rel="image_src" href="/assets/img/images/icon.png">
    <?php $__env->startSection('styles'); ?>
        <?= include_style() ?>
    <?php echo $__env->yieldSection(); ?>
    <?php echo $__env->yieldPushContent('styles'); ?>
    <link rel="stylesheet" href="/themes/motor/css/style.css">
    <link rel="alternate" href="/news/rss" title="RSS News" type="application/rss+xml">
    <meta name="description" content="<?php echo $__env->yieldContent('description', setting('description')); ?>">
    <meta name="keywords" content="<?php echo $__env->yieldContent('keywords', setting('keywords')); ?>">
    <meta name="generator" content="RotorCMS <?php echo e(env('VERSION')); ?>">
</head>
<body>
<!--Design by Vantuz (http://visavi.net)-->

<div id="wrapper">
    <div class="main" id="up">

        <div class="panelTop">
            <img src="/themes/motor/img/panel_top.gif" alt="">
        </div>
        <div class="backgr_top">
            <div class="content">
                <div class="logo">
                    <!-- <a href="/"><span class="logotype"><?php echo e(setting('title')); ?></span></a> -->
                    <a href="/"><img src="/assets/img/images/logo.png" alt="<?php echo e(setting('title')); ?>"></a>
                </div>

                <div class="menu">
                    <a href="/forum">Форум</a>
                    <a href="/book">Гостевая</a>
                    <a href="/news">Новости</a>
                    <a href="/load">Скрипты</a>
                    <a href="/blog">Блоги</a>

                    <span class="mright">

<?php if (is_user()): ?>
    <?php if (is_admin()): ?>

        <?php if (stats_spam()>0): ?>
            <a href="/admin/spam"><span style="color:#ff0000">Спам!</span></a>
        <?php endif; ?>

        <?php if ( user('newchat') < stats_newchat()): ?>
            <a href="/admin/chat"><span style="color:#ff0000">Чат</span></a>
        <?php endif; ?>

            <a href="/admin">Панель</a>
    <?php endif; ?>

    <a href="/menu">Меню</a>
    <a href="/logout" onclick="return confirm('Вы действительно хотите выйти?')">Выход</a>

<?php else: ?>
    <a href="/login<?php echo e(returnUrl()); ?>">Авторизация</a>/
    <a href="/register">Регистрация</a>
<?php endif; ?>

                    </span>

                </div>
            </div>
        </div>

        <div class="backgr">
            <div class="bcontent">
                <div class="mcontentwide">
<?= view('includes/note'); /*Временно пока шаблоны подключаются напрямую*/ ?>