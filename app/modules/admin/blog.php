<?php
view(setting('themes').'/index');

if (isset($_GET['act'])) {
    $act = check($_GET['act']);
} else {
    $act = 'index';
}
if (isset($_GET['id'])) {
    $id = abs(intval($_GET['id']));
} else {
    $id = 0;
}
if (isset($_GET['cid'])) {
    $cid = abs(intval($_GET['cid']));
} else {
    $cid = 0;
}
$page = int(Request::input('page', 1));

if (isAdmin()) {
    //show_title('Управление блогами');

    switch ($action):
    ############################################################################################
    ##                                    Главная страница                                    ##
    ############################################################################################
        case 'index':

            $queryblog = DB::select("SELECT * FROM `catsblog` ORDER BY sort ASC;");
            $blogs = $queryblog -> fetchAll();

            if (count($blogs) > 0) {
                foreach($blogs as $data) {
                    echo '<i class="fa fa-folder-open"></i> ';
                    echo '<b>'.$data['sort'].'. <a href="/admin/blog?act=blog&amp;cid='.$data['id'].'">'.$data['name'].'</a></b> ('.$data['count'].')<br>';

                    if (isAdmin([101])) {
                        echo '<a href="/admin/blog?act=editcats&amp;cid='.$data['id'].'">Редактировать</a> / ';
                        echo '<a href="/admin/blog?act=prodelcats&amp;cid='.$data['id'].'">Удалить</a>';
                    }
                    echo '<br>';
                }
            } else {
                showError('Разделы блогов еще не созданы!');
            }

            if (isAdmin([101])) {
                echo '<br><div class="form">';
                echo '<form action="/admin/blog?act=addcats&amp;uid='.$_SESSION['token'].'" method="post">';
                echo '<b>Заголовок:</b><br>';
                echo '<input type="text" name="name" maxlength="50">';
                echo '<input type="submit" value="Создать раздел"></form></div><br>';

                echo '<i class="fa fa-arrow-circle-up"></i> <a href="/admin/blog?act=restatement&amp;uid='.$_SESSION['token'].'">Пересчитать</a><br>';
            }
        break;

        ############################################################################################
        ##                                    Пересчет счетчиков                                  ##
        ############################################################################################
        case 'restatement':

            $uid = check($_GET['uid']);

            if (isAdmin([101])) {
                if ($uid == $_SESSION['token']) {
                    restatement('blog');

                    setFlash('success', 'Все данные успешно пересчитаны!');
                    redirect("/admin/blog");

                } else {
                    showError('Ошибка! Неверный идентификатор сессии, повторите действие!');
                }
            } else {
                showError('Ошибка! Пересчитывать сообщения могут только суперадмины!');
            }

            echo '<i class="fa fa-arrow-circle-left"></i> <a href="/admin/blog">Вернуться</a><br>';
        break;

        ############################################################################################
        ##                                    Добавление разделов                                 ##
        ############################################################################################
        case 'addcats':

            $uid = check($_GET['uid']);
            $name = check($_POST['name']);

            if (isAdmin([101])) {
                if ($uid == $_SESSION['token']) {
                    if (utfStrlen($name) >= 3 && utfStrlen($name) < 50) {
                        $maxorder = DB::run() -> querySingle("SELECT IFNULL(MAX(sort),0)+1 FROM `catsblog`;");
                        DB::insert("INSERT INTO `catsblog` (sort, `name`) VALUES (?, ?);", [$maxorder, $name]);

                        setFlash('success', 'Новый раздел успешно добавлен!');
                        redirect("/admin/blog");

                    } else {
                        showError('Ошибка! Слишком длинное или короткое название раздела!');
                    }
                } else {
                    showError('Ошибка! Неверный идентификатор сессии, повторите действие!');
                }
            } else {
                showError('Ошибка! Добавлять разделы могут только суперадмины!');
            }

            echo '<i class="fa fa-arrow-circle-left"></i> <a href="/admin/blog">Вернуться</a><br>';
        break;

        ############################################################################################
        ##                          Подготовка к редактированию разделов                          ##
        ############################################################################################
        case 'editcats':

            if (isAdmin([101])) {
                $blogs = DB::run() -> queryFetch("SELECT * FROM `catsblog` WHERE `id`=? LIMIT 1;", [$cid]);

                if (!empty($blogs)) {
                    echo '<b><big>Редактирование</big></b><br><br>';

                    echo '<div class="form">';
                    echo '<form action="/admin/blog?act=changecats&amp;cid='.$cid.'&amp;uid='.$_SESSION['token'].'" method="post">';
                    echo 'Заголовок:<br>';
                    echo '<input type="text" name="name" maxlength="50" value="'.$blogs['name'].'"><br>';
                    echo 'Положение:<br>';
                    echo '<input type="text" name="order" maxlength="2" value="'.$blogs['sort'].'"><br><br>';

                    echo '<input type="submit" value="Изменить"></form></div><br>';
                } else {
                    showError('Ошибка! Данного раздела не существует!');
                }
            } else {
                showError('Ошибка! Изменять разделы могут только суперадмины!');
            }

            echo '<i class="fa fa-arrow-circle-left"></i> <a href="/admin/blog">Вернуться</a><br>';
        break;

        ############################################################################################
        ##                                 Редактирование разделов                                ##
        ############################################################################################
        case 'changecats':

            $uid = check($_GET['uid']);
            $name = check($_POST['name']);
            $order = abs(intval($_POST['order']));

            if (isAdmin([101])) {
                if ($uid == $_SESSION['token']) {
                    if (utfStrlen($name) >= 3 && utfStrlen($name) < 50) {
                        $blogs = DB::run() -> queryFetch("SELECT * FROM `catsblog` WHERE `id`=? LIMIT 1;", [$cid]);

                        if (!empty($blogs)) {
                            DB::update("UPDATE `catsblog` SET sort=?, `name`=? WHERE `id`=?;", [$order, $name, $cid]);

                            setFlash('success', 'Раздел успешно отредактирован!');
                            redirect("/admin/blog");

                        } else {
                            showError('Ошибка! Данного раздела не существует!');
                        }
                    } else {
                        showError('Ошибка! Слишком длинное или короткое название раздела!');
                    }
                } else {
                    showError('Ошибка! Неверный идентификатор сессии, повторите действие!');
                }
            } else {
                showError('Ошибка! Изменять разделы могут только суперадмины!');
            }

            echo '<i class="fa fa-arrow-circle-up"></i> <a href="/admin/blog?act=editcats&amp;cid='.$cid.'">Вернуться</a><br>';
            echo '<i class="fa fa-arrow-circle-left"></i> <a href="/admin/blog">Категории</a><br>';
        break;

        ############################################################################################
        ##                                  Подтвержение удаления                                 ##
        ############################################################################################
        case 'prodelcats':

            if (isAdmin([101])) {
                $blogs = DB::run() -> queryFetch("SELECT * FROM `catsblog` WHERE `id`=? LIMIT 1;", [$cid]);

                if (!empty($blogs)) {
                    echo 'Вы уверены что хотите удалить раздел <b>'.$blogs['name'].'</b> в блогах?<br>';
                    echo '<i class="fa fa-times"></i> <b><a href="/admin/blog?act=delcats&amp;cid='.$cid.'&amp;uid='.$_SESSION['token'].'">Да, уверен!</a></b><br><br>';
                } else {
                    showError('Ошибка! Данного раздела не существует!');
                }
            } else {
                showError('Ошибка! Удалять разделы могут только суперадмины!');
            }

            echo '<i class="fa fa-arrow-circle-left"></i> <a href="/admin/blog">Вернуться</a><br>';
        break;

        ############################################################################################
        ##                                    Удаление раздела                                    ##
        ############################################################################################
        case 'delcats':

            $uid = check($_GET['uid']);

            if (isAdmin([101]) && getUser('login') == env('SITE_ADMIN')) {
                if ($uid == $_SESSION['token']) {
                    $blogs = DB::run() -> queryFetch("SELECT * FROM `catsblog` WHERE `id`=? LIMIT 1;", [$cid]);

                    if (!empty($blogs)) {
                        DB::delete("DELETE FROM `comments` WHERE relate_type=? AND `relate_category_id`=?;", ['blog', $cid]);
                        DB::delete("DELETE FROM `blogs` WHERE `category_id`=?;", [$cid]);
                        DB::delete("DELETE FROM `catsblog` WHERE `id`=?;", [$cid]);

                        setFlash('success', 'Раздел успешно удален!');
                        redirect("/admin/blog");

                    } else {
                        showError('Ошибка! Данного раздела не существует!');
                    }
                } else {
                    showError('Ошибка! Неверный идентификатор сессии, повторите действие!');
                }
            } else {
                showError('Ошибка! Удалять разделы могут только суперадмины!');
            }

            echo '<i class="fa fa-arrow-circle-left"></i> <a href="/admin/blog">Вернуться</a><br>';
        break;

        ############################################################################################
        ##                                       Просмотр статей                                  ##
        ############################################################################################
        case 'blog':

            $cats = DB::run() -> queryFetch("SELECT * FROM `catsblog` WHERE `id`=? LIMIT 1;", [$cid]);

            if (!empty($cats)) {
                //setting('newtitle') = $cats['name'];

                echo '<i class="fa fa-folder-open"></i> <b>'.$cats['name'].'</b> (Статей: '.$cats['count'].')';
                echo ' (<a href="/blog/blog?cid='.$cid.'&amp;page='.$page.'">Обзор</a>)';
                echo '<hr>';

                $total = DB::run() -> querySingle("SELECT count(*) FROM `blogs` WHERE `category_id`=?;", [$cid]);
                $page = paginate(setting('blogpost'), $total);

                if ($total > 0) {

                    $queryblog = DB::select("SELECT * FROM `blogs` WHERE `category_id`=? ORDER BY `time` DESC LIMIT ".$page['offset'].", ".setting('blogpost').";", [$cid]);

                    echo '<form action="/admin/blog?act=delblog&amp;cid='.$cid.'&amp;page='.$page['current'].'&amp;uid='.$_SESSION['token'].'" method="post">';

                    while ($data = $queryblog -> fetch()) {

                        echo '<div class="b"><i class="fa fa-pencil-alt"></i> ';
                        echo '<b><a href="/blog/blog?act=view&amp;id='.$data['id'].'">'.$data['title'].'</a></b> ('.formatNum($data['rating']).')<br>';

                        echo '<input type="checkbox" name="del[]" value="'.$data['id'].'"> ';

                        echo '<a href="/admin/blog?act=editblog&amp;cid='.$cid.'&amp;id='.$data['id'].'&amp;page='.$page['current'].'">Редактировать</a> / ';
                        echo '<a href="/admin/blog?act=moveblog&amp;cid='.$cid.'&amp;id='.$data['id'].'&amp;page='.$page['current'].'">Переместить</a></div>';

                        echo '<div>Автор: '.profile($data['user']).' ('.dateFixed($data['time']).')<br>';
                        echo 'Просмотров: '.$data['visits'].'<br>';
                        echo '<a href="/blog/blog?act=comments&amp;id='.$data['id'].'">Комментарии</a> ('.$data['comments'].')<br>';
                        echo '</div>';
                    }

                    echo '<br><input type="submit" value="Удалить выбранное"></form>';

                    pagination($page);
                } else {
                    showError('В данном разделе еще нет статей!');
                }
            } else {
                showError('Ошибка! Данного раздела не существует!');
            }

            echo '<i class="fa fa-arrow-circle-up"></i> <a href="/admin/blog">Категории</a><br>';
        break;

        ############################################################################################
        ##                            Подготовка к редактированию статьи                          ##
        ############################################################################################
        case 'editblog':

            $blogs = DB::run() -> queryFetch("SELECT * FROM `blogs` WHERE `id`=? LIMIT 1;", [$id]);

            if (!empty($blogs)) {
                echo '<b><big>Редактирование</big></b><br><br>';

                echo '<div class="form next">';
                echo '<form action="/admin/blog?act=addeditblog&amp;cid='.$cid.'&amp;id='.$id.'&amp;page='.$page.'&amp;uid='.$_SESSION['token'].'" method="post">';

                echo 'Заголовок:<br>';
                echo '<input type="text" name="title" size="50" maxlength="50" value="'.$blogs['title'].'"><br>';
                echo 'Текст:<br>';
                echo '<textarea id="markItUp" cols="25" rows="15" name="text">'.$blogs['text'].'</textarea><br>';
                echo 'Автор:<br>';
                echo '<input type="text" name="user" maxlength="20" value="'.$blogs['user'].'"><br>';
                echo 'Метки:<br>';
                echo '<input type="text" name="tags" size="50" maxlength="100" value="'.$blogs['tags'].'"><br>';

                echo '<input type="submit" value="Изменить"></form></div><br>';
            } else {
                showError('Ошибка! Данной статьи не существует!');
            }

            echo '<i class="fa fa-arrow-circle-left"></i> <a href="/admin/blog?act=blog&amp;cid='.$cid.'&amp;page='.$page.'">Вернуться</a><br>';
            echo '<i class="fa fa-arrow-circle-up"></i> <a href="/admin/blog">Категории</a><br>';
        break;

        ############################################################################################
        ##                                  Редактирование статьи                                ##
        ############################################################################################
        case 'addeditblog':

            $uid = check($_GET['uid']);
            $title = check($_POST['title']);
            $text = check($_POST['text']);
            $user = check($_POST['user']);
            $tags = check($_POST['tags']);

            if ($uid == $_SESSION['token']) {
                if (utfStrlen($title) >= 5 && utfStrlen($title) <= 50) {
                    if (utfStrlen($text) >= 100 && utfStrlen($text) <= setting('maxblogpost')) {
                        if (utfStrlen($tags) >= 2 && utfStrlen($tags) <= 50) {
                            if (preg_match('|^[a-z0-9\-]+$|i', $user)) {
                                $queryblog = DB::run() -> querySingle("SELECT `id` FROM `blogs` WHERE `id`=? LIMIT 1;", [$id]);
                                if (!empty($queryblog)) {

                                    DB::update("UPDATE `blogs` SET `title`=?, `text`=?, `user`=?, `tags`=? WHERE `id`=?;", [$title, $text, $user, $tags, $id]);

                                    setFlash('success', 'Статья успешно отредактирована!');
                                    redirect("/admin/blog?act=blog&cid=$cid&page=$page");

                                } else {
                                    showError('Ошибка! Данной статьи не существует!');
                                }
                            } else {
                                showError('Ошибка! Недопустимые символы в логине! Разрешены только знаки латинского алфавита и цифры!');
                            }
                        } else {
                            showError('Ошибка! Слишком длинные или короткие метки статьи (от 2 до 50 символов)!');
                        }
                    } else {
                        showError('Ошибка! Слишком длинный или короткий текст статьи (от 100 до '.setting('maxblogpost').' символов)!');
                    }
                } else {
                    showError('Ошибка! Слишком длинный или короткий заголовок (от 5 до 50 символов)!');
                }
            } else {
                showError('Ошибка! Неверный идентификатор сессии, повторите действие!');
            }

            echo '<i class="fa fa-arrow-circle-left"></i> <a href="/admin/blog?act=editblog&amp;id='.$id.'&amp;page='.$page.'">Вернуться</a><br>';
            echo '<i class="fa fa-arrow-circle-up"></i> <a href="/admin/blog?act=blog&amp;cid='.$cid.'&amp;page='.$page.'">В раздел</a><br>';
        break;

        ############################################################################################
        ##                               Подготовка к перемещению статьи                          ##
        ############################################################################################
        case 'moveblog':

            $blogs = DB::run() -> queryFetch("SELECT * FROM `blogs` WHERE `id`=? LIMIT 1;", [$id]);

            if (!empty($blogs)) {
                echo '<i class="fa fa-file"></i> <b>'.$blogs['title'].'</b><br><br>';

                $querycats = DB::select("SELECT `id`, `name` FROM `catsblog` ORDER BY sort ASC;");
                $cats = $querycats -> fetchAll();

                if (count($cats) > 1) {
                    echo '<div class="form">';
                    echo '<form action="/admin/blog?act=addmoveblog&amp;cid='.$blogs['category_id'].'&amp;id='.$id.'&amp;uid='.$_SESSION['token'].'" method="post">';

                    echo 'Выберите раздел для перемещения:<br>';
                    echo '<select name="section">';
                    echo '<option value="0">Список разделов</option>';

                    foreach ($cats as $data) {
                        if ($blogs['category_id'] != $data['id']) {
                            echo '<option value="'.$data['id'].'">'.$data['name'].'</option>';
                        }
                    }

                    echo '</select>';
                    echo '<input type="submit" value="Переместить"></form></div><br>';
                } elseif(count($cats) == 1) {
                    showError('Нет разделов для перемещения!');
                } else {
                    showError('Ошибка! Разделы блогов еще не созданы!');
                }
            } else {
                showError('Ошибка! Данной статьи не существует!');
            }

            echo '<i class="fa fa-arrow-circle-left"></i> <a href="/admin/blog?act=blog&amp;cid='.$cid.'&amp;page='.$page.'">Вернуться</a><br>';
        break;

        ############################################################################################
        ##                                    Перемещение статьи                                  ##
        ############################################################################################
        case 'addmoveblog':

            $uid = check($_GET['uid']);
            $section = abs(intval($_POST['section']));

            if ($uid == $_SESSION['token']) {
                $querycats = DB::run() -> querySingle("SELECT `id` FROM `catsblog` WHERE `id`=? LIMIT 1;", [$section]);
                if (!empty($querycats)) {
                    $queryblog = DB::run() -> querySingle("SELECT `id` FROM `blogs` WHERE `id`=? LIMIT 1;", [$id]);
                    if (!empty($queryblog)) {
                        DB::update("UPDATE `blogs` SET `category_id`=? WHERE `id`=?;", [$section, $id]);
                        DB::update("UPDATE `comments` SET `relate_category_id`=? WHERE relate_type=? AND `relate_id`=?;", [$section, 'blog', $id]);
                        // Обновление счетчиков
                        DB::update("UPDATE `catsblog` SET `count`=`count`+1 WHERE `id`=?", [$section]);
                        DB::update("UPDATE `catsblog` SET `count`=`count`-1 WHERE `id`=?", [$cid]);

                        setFlash('success', 'Статья успешно перемещена!');
                        redirect("/admin/blog?act=blog&cid=$section");

                    } else {
                        showError('Ошибка! Статьи для перемещения не существует!');
                    }
                } else {
                    showError('Ошибка! Выбранного раздела не существует!');
                }
            } else {
                showError('Ошибка! Неверный идентификатор сессии, повторите действие!');
            }

            echo '<i class="fa fa-arrow-circle-left"></i> <a href="/admin/blog?act=moveblog&amp;cid='.$cid.'&amp;id='.$id.'">Вернуться</a><br>';
            echo '<i class="fa fa-arrow-circle-up"></i> <a href="/admin/blog?act=blog&amp;cid='.$cid.'">К блогам</a><br>';
        break;

        ############################################################################################
        ##                                     Удаление статей                                    ##
        ############################################################################################
        case 'delblog':

            $uid = check($_GET['uid']);
            if (isset($_POST['del'])) {
                $del = intar($_POST['del']);
            } elseif (isset($_GET['del'])) {
                $del = [abs(intval($_GET['del']))];
            } else {
                $del = 0;
            }

            if ($uid == $_SESSION['token']) {
                if (!empty($del)) {
                    $del = implode(',', $del);

                    DB::delete("DELETE FROM `comments` WHERE relate_type='blog' AND `relate_id` IN (".$del.");");
                    $delblogs = DB::run() -> exec("DELETE FROM `blogs` WHERE `id` IN (".$del.");");
                    // Обновление счетчиков
                    DB::update("UPDATE `catsblog` SET `count`=`count`-? WHERE `id`=?", [$delblogs, $cid]);

                    setFlash('success', 'Выбранные статьи успешно удалены!');
                    redirect("/admin/blog?act=blog&cid=$cid&page=$page");

                } else {
                    showError('Ошибка! Отсутствуют выбранные статьи для удаления!');
                }
            } else {
                showError('Ошибка! Неверный идентификатор сессии, повторите действие!');
            }

            echo '<i class="fa fa-arrow-circle-left"></i> <a href="/admin/blog?act=blog&amp;cid='.$cid.'&amp;page='.$page.'">Вернуться</a><br>';
        break;

    endswitch;

    echo '<i class="fa fa-wrench"></i> <a href="/admin">В админку</a><br>';

} else {
    redirect('/');
}

view(setting('themes').'/foot');
