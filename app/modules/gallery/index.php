<?php
App::view($config['themes'].'/index');

$act = (isset($_GET['act'])) ? check($_GET['act']) : 'index';
$uz = (empty($_GET['uz'])) ? check($log) : check($_GET['uz']);
$gid = (isset($_GET['gid'])) ? abs(intval($_GET['gid'])) : 0;
$page = abs(intval(Request::input('page', 1)));

show_title('Галерея сайта');

switch ($act):
############################################################################################
##                                    Главная страница                                    ##
############################################################################################
case 'index':

    $photos = [];
    $total = DB::run() -> querySingle("SELECT count(*) FROM `photo`;");
    $page = App::paginate(App::setting('fotolist'), $total);

    if ($total > 0) {

        $config['newtitle'] = 'Галерея сайта (Стр. '.$page['current'].')';

        $queryphoto = DB::run() -> query("SELECT * FROM `photo` ORDER BY `time` DESC LIMIT ".$page['offset'].", ".$config['fotolist'].";");
        $photos = $queryphoto->fetchAll();
    }

    render('gallery/index', compact('photos', 'page', 'total'));
break;

    ############################################################################################
    ##                             Просмотр полной фотографии                                 ##
    ############################################################################################
    case 'view':

        $photo = DB::run() -> queryFetch("SELECT * FROM `photo` WHERE `id`=? LIMIT 1;", [$gid]);

        render('gallery/view', compact('photo', 'page'));

    break;

    ############################################################################################
    ##                                       Оценка фотографии                                ##
    ############################################################################################
    case 'vote':

        $uid = check($_GET['uid']);
        $vote = check($_GET['vote']);

        if (is_user()) {
            if ($uid == $_SESSION['token']) {
                if ($vote == 'up' || $vote == 'down') {

                    $score = ($vote == 'up') ? 1 : -1;

                    $data = DB::run() -> queryFetch("SELECT * FROM `photo` WHERE `id`=? LIMIT 1;", [$gid]);

                    if (!empty($data)) {
                        if ($log != $data['user']) {
                            $queryrated = DB::run() -> querySingle("SELECT `id` FROM `pollings` WHERE relate_type=? AND `relate_id`=? AND `user`=? LIMIT 1;", ['gallery', $gid, $log]);

                            if (empty($queryrated)) {
                                $expiresrated = SITETIME + 3600 * $config['photoexprated'];

                                DB::run() -> query("DELETE FROM `pollings` WHERE relate_type=? AND `time`<?;", ['gallery', SITETIME]);
                                DB::run() -> query("INSERT INTO `pollings` (relate_type, `relate_id`, `user`, `time`) VALUES (?, ?, ?, ?);", ['gallery', $gid, $log, $expiresrated]);
                                DB::run() -> query("UPDATE `photo` SET `rating`=`rating`+? WHERE `id`=?;", [$score, $gid]);

                                notice('Ваша оценка принята! Рейтинг фотографии: '.format_num($data['rating'] + $score));
                                redirect("/gallery?act=view&gid=$gid");

                            } else {
                                show_error('Ошибка! Вы уже оценивали данную фотографию!');
                            }
                        } else {
                            show_error('Ошибка! Нельзя голосовать за свои фотографии!');
                        }
                    } else {
                        show_error('Ошибка! Данной фотографии не существует!');
                    }
                } else {
                    show_error('Ошибка! Необходимо проголосовать за или против фотографии!');
                }
            } else {
                show_error('Ошибка! Неверный идентификатор сессии, повторите действие!');
            }
        } else {
            show_login('Вы не авторизованы, для голосования за фотографии, необходимо');
        }

        echo '<i class="fa fa-arrow-circle-left"></i> <a href="/gallery?act=view&amp;gid='.$gid.'">Вернуться</a><br />';
    break;

    ############################################################################################
    ##                                  Форма загрузки фото                                   ##
    ############################################################################################
    case 'addphoto':

        $config['newtitle'] = 'Добавление фотографии';

        if (is_user()) {
            echo '<div class="form">';
            echo '<form action="/gallery?act=add&amp;uid='.$_SESSION['token'].'" method="post" enctype="multipart/form-data">';
            echo 'Прикрепить фото:<br /><input type="file" name="photo" /><br />';
            echo 'Название: <br /><input type="text" name="title" /><br />';
            echo 'Подпись к фото: <br /><textarea cols="25" rows="5" name="text"></textarea><br />';

            echo 'Закрыть комментарии: <input name="closed" type="checkbox" value="1" /><br />';

            echo '<input type="submit" value="Добавить" /></form></div><br />';

            echo 'Разрешается добавлять фотки с расширением jpg, jpeg, gif и png<br />';
            echo 'Весом не более '.formatsize($config['filesize']).' и размером от 100 до '.(int)$config['fileupfoto'].' px<br /><br />';
        } else {
            show_login('Вы не авторизованы, чтобы добавить фотографию, необходимо');
        }

        echo '<i class="fa fa-arrow-circle-left"></i> <a href="/gallery">Вернуться</a><br />';
    break;

    ############################################################################################
    ##                                   Загрузка фото                                        ##
    ############################################################################################
    case 'add':

        $config['newtitle'] = 'Результат добавления';

        $uid = check($_GET['uid']);
        $title = check($_POST['title']);
        $text = (!empty($_POST['text'])) ? check($_POST['text']) : '';
        $closed = (empty($_POST['closed'])) ? 0 : 1;

        if (is_user()) {
            if ($uid == $_SESSION['token']) {
                if (is_uploaded_file($_FILES['photo']['tmp_name'])) {
                    if (utf_strlen($title) >= 5 && utf_strlen($title) <= 50) {
                        if (utf_strlen($text) <= 1000) {
                            if (is_flood($log)) {

                                $handle = upload_image(
                                    $_FILES['photo'],
                                    $config['filesize'],
                                    $config['fileupfoto'],
                                    uniqid()
                                );

                                if ($handle) {
                                    $handle -> process(HOME.'/uploads/pictures/');
                                    if ($handle -> processed) {

                                        $rek = DBM::run()->insert('photo', [
                                            'user'  => $log,
                                            'title'  => $title,
                                            'text' => antimat($text),
                                            'link' => $handle->file_dst_name,
                                            'time' => SITETIME,
                                            'closed' => $closed,
                                        ]);

                                        $handle -> clean();

                                        notice('Фотография успешно загружена!');
                                        redirect("/gallery");

                                    } else {
                                        show_error($handle->error);
                                    }
                                } else {
                                    show_error('Ошибка! Не удалось загрузить изображение!');
                                }
                            } else {
                                show_error('Антифлуд! Вы слишком часто добавляете фотографии!');
                            }
                        } else {
                            show_error('Слишком длинное описание (Необходимо до 1000 символов)!');
                        }
                    } else {
                        show_error('Слишком длинное или короткое название (Необходимо от 5 до 50 символов)!');
                    }
                } else {
                    show_error('Ошибка! Не удалось загрузить изображение!');
                }
            } else {
                show_error('Ошибка! Неверный идентификатор сессии, повторите действие!');
            }
        } else {
            show_login('Вы не авторизованы, чтобы добавить фотографию, необходимо');
        }

        echo '<i class="fa fa-arrow-circle-left"></i> <a href="/gallery?act=addphoto">Вернуться</a><br />';
        break;
    ############################################################################################
    ##                                 Редактирование фото                                    ##
    ############################################################################################
    case 'edit':

        if (is_user()) {
            $photo = DB::run() -> queryFetch("SELECT * FROM `photo` WHERE `id`=? AND `user`=? LIMIT 1;", [$gid, $log]);

            if (!empty($photo)) {

                echo '<div class="form">';
                echo '<form action="/gallery?act=change&amp;gid='.$gid.'&amp;page='.$page.'&amp;uid='.$_SESSION['token'].'" method="post">';
                echo 'Название: <br /><input type="text" name="title" value="'.$photo['title'].'" /><br />';
                echo 'Подпись к фото: <br /><textarea cols="25" rows="5" name="text">'.$photo['text'].'</textarea><br />';

                echo 'Закрыть комментарии: ';
                $checked = ($photo['closed'] == 1) ? ' checked="checked"' : '';
                echo '<input name="closed" type="checkbox" value="1"'.$checked.' /><br />';

                echo '<input type="submit" value="Изменить" /></form></div><br />';
            } else {
                show_error('Ошибка! Фотография удалена или вы не автор этой фотографии!');
            }
        } else {
            show_login('Вы не авторизованы, чтобы редактировать фотографию, необходимо');
        }

        echo '<i class="fa fa-arrow-circle-up"></i> <a href="/gallery/album?act=photo&amp;uz='.$uz.'&amp;page='.$page.'">Альбом</a><br />';
        echo '<i class="fa fa-arrow-circle-left"></i> <a href="/gallery">Галерея</a><br />';
    break;

    ############################################################################################
    ##                                 Изменение описания                                     ##
    ############################################################################################
    case 'change':

        $uid = check($_GET['uid']);
        $title = check($_POST['title']);
        $text = (!empty($_POST['text'])) ? check($_POST['text']) : '';
        $closed = (empty($_POST['closed'])) ? 0 : 1;

        if ($uid == $_SESSION['token']) {
            if (is_user()) {
                $photo = DB::run() -> queryFetch("SELECT * FROM `photo` WHERE `id`=? AND `user`=? LIMIT 1;", [$gid, $log]);

                if (!empty($photo)) {
                    if (utf_strlen($title) >= 5 && utf_strlen($title) <= 50) {
                        if (utf_strlen($text) <= 1000) {

                            $text = antimat($text);

                            DB::run() -> query("UPDATE `photo` SET `title`=?, `text`=?, `closed`=? WHERE `id`=?;", [$title, $text, $closed, $gid]);

                            notice('Фотография успешно отредактирована!');
                            redirect("/gallery/album?act=photo&uz=$uz&page=$page");

                        } else {
                            show_error('Ошибка! Слишком длинное описание (Необходимо до 1000 символов)!');
                        }
                    } else {
                        show_error('Ошибка! Слишком длинное или короткое название!');
                    }
                } else {
                    show_error('Ошибка! Фотография удалена или вы не автор этой фотографии!');
                }
            } else {
                show_login('Вы не авторизованы, чтобы редактировать фотографию, необходимо');
            }
        } else {
            show_error('Ошибка! Неверный идентификатор сессии, повторите действие!');
        }

        echo '<i class="fa fa-arrow-circle-up"></i> <a href="/gallery?act=edit&amp;gid='.$gid.'&amp;page='.$page.'">Вернуться</a><br />';
        echo '<i class="fa fa-arrow-circle-left"></i> <a href="/gallery">Галерея</a><br />';
    break;

    ############################################################################################
    ##                                   Список комментариев                                  ##
    ############################################################################################
    case 'comments':

        $photo = DB::run() -> queryFetch("SELECT * FROM `photo` WHERE `id`=? LIMIT 1;", [$gid]);

        if (!empty($photo)) {
            $config['newtitle'] = 'Комментарии - '.$photo['title'];

            echo '<i class="fa fa-picture-o"></i> <b><a href="/gallery?act=view&amp;gid='.$photo['id'].'">'.$photo['title'].'</a></b><br /><br />';

            echo '<a href="/gallery?act=comments&amp;gid='.$gid.'&amp;rand='.mt_rand(100, 999).'">Обновить</a><hr />';

            $total = DB::run() -> querySingle("SELECT count(*) FROM `comments` WHERE relate_type=? AND `relate_id`=?;", ['gallery', $gid]);
            $page = App::paginate(App::setting('postgallery'), $total);

            if ($total > 0) {
                $is_admin = is_admin();
                if ($is_admin) {
                    echo '<form action="/gallery?act=delcomm&amp;gid='.$gid.'&amp;page='.$page['current'].'&amp;uid='.$_SESSION['token'].'" method="post">';
                }

                $querycomm = DB::run() -> query("SELECT * FROM `comments` WHERE relate_type=? AND `relate_id`=? ORDER BY `time` ASC LIMIT ".$page['offset'].", ".$config['postgallery'].";", ['gallery', $gid]);

                while ($data = $querycomm -> fetch()) {

                    echo '<div class="b">';
                    echo '<div class="img">'.user_avatars($data['user']).'</div>';

                    if ($is_admin) {
                        echo '<span class="imgright"><input type="checkbox" name="del[]" value="'.$data['id'].'" /></span>';
                    }

                    echo '<b>'.profile($data['user']).'</b> <small>('.date_fixed($data['time']).')</small><br />';
                    echo user_title($data['user']).' '.user_online($data['user']).'</div>';

                    if ($log == $data['user'] && $data['time'] + 600 > SITETIME) {
                        echo '<div class="right"><a href="/gallery?act=editcomm&amp;gid='.$gid.'&amp;cid='.$data['id'].'&amp;page='.$page['current'].'">Редактировать</a></div>';
                    }

                    echo '<div>'.App::bbCode($data['text']).'<br />';

                    if (is_admin() || empty($config['anonymity'])) {
                        echo '<span class="data">('.$data['brow'].', '.$data['ip'].')</span>';
                    }

                    echo '</div>';
                }

                if ($is_admin) {
                    echo '<span class="imgright"><input type="submit" value="Удалить выбранное" /></span></form>';
                }

                App::pagination($page);
            }

            if (empty($photo['closed'])) {

                if (empty($total)) {
                    show_error('Комментариев еще нет!');
                }

                if (is_user()) {
                    echo '<div class="form">';
                    echo '<form action="/gallery?act=addcomm&amp;gid='.$gid.'&amp;uid='.$_SESSION['token'].'" method="post">';

                    echo '<textarea id="markItUp" cols="25" rows="5" name="msg"></textarea><br />';
                    echo '<input type="submit" value="Написать" /></form></div><br />';

                    echo '<a href="/rules">Правила</a> / ';
                    echo '<a href="/smiles">Смайлы</a> / ';
                    echo '<a href="/tags">Теги</a><br /><br />';
                } else {
                    show_login('Вы не авторизованы, чтобы добавить комментарий, необходимо');
                }
            } else {
                show_error('Комментирование данной фотографии закрыто!');
            }

            echo '<i class="fa fa-arrow-circle-up"></i> <a href="/gallery/album?act=photo&amp;uz='.$photo['user'].'">Альбом</a><br />';
        } else {
            show_error('Ошибка! Данного изображение не существует!');
        }

        echo '<i class="fa fa-arrow-circle-left"></i> <a href="/gallery">Галерея</a><br />';
    break;

    ############################################################################################
    ##                                   Запись комментариев                                  ##
    ############################################################################################
    case 'addcomm':

        $uid = check($_GET['uid']);
        $msg = check($_POST['msg']);

        $config['newtitle'] = 'Добавление комментария';

        if (is_user()) {
            if ($uid == $_SESSION['token']) {
                if (utf_strlen($msg) >= 5 && utf_strlen($msg) <= 1000) {
                    $data = DB::run() -> queryFetch("SELECT * FROM `photo` WHERE `id`=? LIMIT 1;", [$gid]);

                    if (!empty($data)) {
                        if (empty($data['closed'])) {
                            if (is_flood($log)) {
                                $msg = antimat($msg);

                                DB::run() -> query("INSERT INTO `comments` (relate_type, relate_category_id, `relate_id`, `text`, `user`, `time`, `ip`, `brow`) VALUES (?, ?, ?, ?, ?, ?, ?, ?);", ['gallery', 0, $gid, $msg, $log, SITETIME, App::getClientIp(), App::getUserAgent()]);

                                DB::run() -> query("DELETE FROM `comments` WHERE relate_type=? AND `relate_id`=? AND `time` < (SELECT MIN(`time`) FROM (SELECT `time` FROM `comments` WHERE relate_type=? AND `relate_id`=? ORDER BY `time` DESC LIMIT ".$config['maxpostgallery'].") AS del);", ['gallery', $gid, 'gallery', $gid]);

                                DB::run() -> query("UPDATE `photo` SET `comments`=`comments`+1 WHERE `id`=?;", [$gid]);
                                DB::run() -> query("UPDATE `users` SET `allcomments`=`allcomments`+1, `point`=`point`+1, `money`=`money`+5 WHERE `login`=?", [$log]);

                                notice('Комментарий успешно добавлен!');
                                redirect("/gallery?act=end&gid=$gid");

                            } else {
                                show_error('Антифлуд! Разрешается отправлять комментарии раз в '.flood_period().' секунд!');
                            }
                        } else {
                            show_error('Ошибка! Комментирование данной фотографии запрещено!');
                        }
                    } else {
                        show_error('Ошибка! Данного изображения не существует!');
                    }
                } else {
                    show_error('Ошибка! Слишком длинный или короткий комментарий!');
                }
            } else {
                show_error('Ошибка! Неверный идентификатор сессии, повторите действие!');
            }
        } else {
            show_login('Вы не авторизованы, чтобы добавить комментарий, необходимо');
        }

        echo '<i class="fa fa-arrow-circle-left"></i> <a href="/gallery?act=comments&amp;gid='.$gid.'">Вернуться</a><br />';
        echo '<i class="fa fa-arrow-circle-up"></i> <a href="/gallery">В галерею</a><br />';
    break;

    ############################################################################################
    ##                                Подготовка к редактированию                             ##
    ############################################################################################
    case 'editcomm':

        $cid = abs(intval($_GET['cid']));

        if (is_user()) {
            $comm = DB::run() -> queryFetch("SELECT `c`.*, `p`.`closed` FROM `comments` c LEFT JOIN `photo` p ON `c`.`relate_id`=`p`.`id` WHERE relate_type=? AND c.`id`=? AND c.`user`=? LIMIT 1;", ['gallery', $cid, $log]);

            if (!empty($comm)) {
                if (empty($comm['closed'])) {
                    if ($comm['time'] + 600 > SITETIME) {

                        echo '<i class="fa fa-pencil"></i> <b>'.nickname($comm['user']).'</b> <small>('.date_fixed($comm['time']).')</small><br /><br />';

                        echo '<div class="form">';
                        echo '<form action="/gallery?act=changecomm&amp;gid='.$comm['relate_id'].'&amp;cid='.$cid.'&amp;page='.$page.'&amp;uid='.$_SESSION['token'].'" method="post">';
                        echo '<textarea id="markItUp" cols="25" rows="5" name="msg" id="msg">'.$comm['text'].'</textarea><br />';
                        echo '<input type="submit" value="Редактировать" /></form></div><br />';

                    } else {
                        show_error('Ошибка! Редактирование невозможно, прошло более 10 минут!!');
                    }
                } else {
                    show_error('Ошибка! Редактирование невозможно, комментирование запрещено!');
                }
            } else {
                show_error('Ошибка! Комментарий удален или вы не автор этого комментария!');
            }
        } else {
            show_login('Вы не авторизованы, чтобы редактировать комментарии, необходимо');
        }

        echo '<i class="fa fa-arrow-circle-left"></i> <a href="/gallery?act=comments&amp;gid='.$gid.'&amp;page='.$page.'">Вернуться</a><br />';
    break;

    ############################################################################################
    ##                                  Редактирование комментария                            ##
    ############################################################################################
    case 'changecomm':

        $uid = check($_GET['uid']);
        $cid = abs(intval($_GET['cid']));
        $msg = check($_POST['msg']);

        if (is_user()) {
            if ($uid == $_SESSION['token']) {
                if (utf_strlen($msg) >= 5 && utf_strlen($msg) <= 1000) {
                    $comm = DB::run() -> queryFetch("SELECT `c`.*, `p`.`closed` FROM `comments` c LEFT JOIN `photo` p ON `c`.`relate_id`=`p`.`id` WHERE relate_type=? AND c.`id`=? AND c.`user`=? LIMIT 1;", ['gallery', $cid, $log]);

                    if (!empty($comm)) {
                        if (empty($comm['closed'])) {
                            if ($comm['time'] + 600 > SITETIME) {

                                $msg = antimat($msg);

                                DB::run() -> query("UPDATE `comments` SET `text`=? WHERE relate_type=? AND `id`=?;", [$msg, 'gallery', $cid]);

                                notice('Комментарий успешно отредактирован!');
                                redirect("/gallery?act=comments&gid=$gid&page=$page");

                            } else {
                                show_error('Ошибка! Редактирование невозможно, прошло более 10 минут!');
                            }
                        } else {
                            show_error('Ошибка! Редактирование невозможно, комментирование запрещено!');
                        }
                    } else {
                        show_error('Ошибка! Комментарий удален или вы не автор этого комментария!');
                    }
                } else {
                    show_error('Ошибка! Слишком длинный или короткий комментарий!');
                }
            } else {
                show_error('Ошибка! Неверный идентификатор сессии, повторите действие!');
            }
        } else {
            show_login('Вы не авторизованы, чтобы редактировать комментарии, необходимо');
        }

        echo '<i class="fa fa-arrow-circle-left"></i> <a href="/gallery?act=editcomm&amp;gid='.$gid.'&amp;cid='.$cid.'&amp;page='.$page.'">Вернуться</a><br />';
    break;

    ############################################################################################
    ##                                 Удаление комментариев                                  ##
    ############################################################################################
    case 'delcomm':

        $uid = check($_GET['uid']);
        if (isset($_POST['del'])) {
            $del = intar($_POST['del']);
        } else {
            $del = 0;
        }

        if (is_admin()) {
            if ($uid == $_SESSION['token']) {
                if (!empty($del)) {
                    $del = implode(',', $del);

                    $delcomments = DB::run() -> exec("DELETE FROM comments WHERE relate_type='gallery' AND id IN (".$del.") AND relate_id=".$gid.";");
                    DB::run() -> query("UPDATE photo SET comments=comments-? WHERE id=?;", [$delcomments, $gid]);

                    notice('Выбранные комментарии успешно удалены!');
                    redirect("/gallery?act=comments&gid=$gid&page=$page");

                } else {
                    show_error('Ошибка! Отстутствуют выбранные комментарии для удаления!');
                }
            } else {
                show_error('Ошибка! Неверный идентификатор сессии, повторите действие!');
            }
        } else {
            show_error('Ошибка! Удалять комментарии могут только модераторы!');
        }

        echo '<i class="fa fa-arrow-circle-left"></i> <a href="/gallery?act=comments&amp;gid='.$gid.'">Вернуться</a><br />';
    break;

    ############################################################################################
    ##                                   Удаление фотографий                                  ##
    ############################################################################################
    case 'delphoto':

        $uid = check($_GET['uid']);

        if (is_user()) {
            if ($uid == $_SESSION['token']) {
                if (is_writeable(HOME.'/uploads/pictures')) {
                    $querydel = DB::run() -> queryFetch("SELECT `id`, `link`, `comments` FROM `photo` WHERE `id`=? AND `user`=? LIMIT 1;", [$gid, $log]);
                    if (!empty($querydel)) {
                        if (empty($querydel['comments'])) {
                            DB::run() -> query("DELETE FROM `photo` WHERE `id`=? LIMIT 1;", [$querydel['id']]);
                            DB::run() -> query("DELETE FROM `comments` WHERE relate_type=? AND `relate_id`=?;", ['gallery', $querydel['id']]);

                            unlink_image('uploads/pictures/', $querydel['link']);

                            notice('Фотография успешно удалена!');
                            redirect("/gallery/album?act=photo&page=$page");

                        } else {
                            show_error('Ошибка! Запрещено удалять фотографии к которым имеются комментарии!');
                        }
                    } else {
                        show_error('Ошибка! Данная фотография не существует или вы не автор этой фотографии!');
                    }
                } else {
                    show_error('Ошибка! Не установлены атрибуты доступа на дирекоторию с фотографиями!');
                }
            } else {
                show_error('Ошибка! Неверный идентификатор сессии, повторите действие!');
            }
        } else {
            show_login('Вы не авторизованы, чтобы удалять фотографии, необходимо');
        }

        echo '<i class="fa fa-arrow-circle-left"></i> <a href="/gallery/album?act=photo&amp;page='.$page.'">Вернуться</a><br />';
    break;

    ############################################################################################
    ##                             Переадресация на последнюю страницу                        ##
    ############################################################################################
    case 'end':

        $query = DB::run() -> queryFetch("SELECT count(*) as `total_comments` FROM `comments` WHERE relate_type=? AND `relate_id`=? LIMIT 1;", ['gallery', $gid]);

        if (!empty($query)) {

            $total_comments = (empty($query['total_comments'])) ? 1 : $query['total_comments'];
            $end = ceil($total_comments / $config['postgallery']);

            redirect("/gallery?act=comments&gid=$gid&page=$end");

        } else {
            show_error('Ошибка! Комментарий к данному изображению не существует!');
        }

        echo '<i class="fa fa-arrow-circle-left"></i> <a href="/gallery">В галерею</a><br />';
    break;

    ############################################################################################
    ##                                   Удаление фотографий                                  ##
    ############################################################################################
    /**
    * case 'delphoto':
    *
    * $uid = check($_GET['uid']);
    * if (isset($_POST['del'])) {$del = intar($_POST['del']);} else {$del = 0;}
    *
    * if (is_user()){
    * if ($uid==$_SESSION['token']){
    * if (!empty($del)){
    *
    * $del = implode(',', $del);
    *
    * if (is_writeable(HOME.'/uploads/pictures')){
    *
    * $querydel = DB::run()->query("SELECT `id`, `link` FROM `photo` WHERE `id` IN (".$del.") AND `user`=?;", array($log));
    * $arr_photo = $querydel->fetchAll();
    *
    * if (count($arr_photo)>0){
    * foreach ($arr_photo as $delete){
    * DB::run()->query("DELETE FROM `photo` WHERE `id`=? LIMIT 1;", array($delete['id']));
    * DB::run()->query("DELETE FROM `comments` WHERE relate_type=? AND `relate_id`=?;", array('gallery', $delete['id']));
    * if (file_exists(HOME.'/uploads/pictures/'.$delete['link'])) {unlink(HOME.'/uploads/pictures/'.$delete['link']);}
    * }
    *
    * notice('Выбранные фотографии успешно удалены!');
    * redirect("/gallery?act=album&page=$page");
    *
    * } else {show_error('Ошибка! Данных фотографий не существует или вы не автор этих фотографий!');}
    * } else {show_error('Ошибка! Не установлены атрибуты доступа на дирекоторию с фотографиями!');}
    * } else {show_error('Ошибка! Отсутствуют выбранные фотографии!');}
    * } else {show_error('Ошибка! Неверный идентификатор сессии, повторите действие!');}
    * } else {show_login('Вы не авторизованы, чтобы удалять фотографии, необходимо');}
    *
    * echo '<i class="fa fa-arrow-circle-left"></i> <a href="/gallery?act=album&amp;page='.$page.'">Вернуться</a><br />';
    * break;
    */

endswitch;

App::view($config['themes'].'/foot');
