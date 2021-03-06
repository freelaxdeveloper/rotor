<?php
view(setting('themes').'/index');

$act = (isset($_GET['act'])) ? check($_GET['act']) : 'index';
$used = (!empty($_GET['used'])) ? 1  : 0;
$page = int(Request::input('page', 1));

if (isAdmin([101, 102, 103])) {
    //show_title('Приглашения');

switch ($action):
############################################################################################
##                                    Главная страница                                    ##
############################################################################################
case 'index':

    if (empty(setting('invite'))) {
        echo '<i class="fa fa-exclamation-circle"></i> <span style="color:#ff0000"><b>Внимание! Регистрация по приглашения выключена!</b></span><br><br>';
    }

    if (empty($used)){
        echo '<b>Неиспользованные</b> / <a href="/admin/invitations?used=1">Использованные</a><hr>';
    } else {
        echo '<a href="/admin/invitations">Неиспользованные</a> / <b>Использованные</b><hr>';
    }

    $total = DB::run() -> querySingle("SELECT COUNT(*) FROM `invite` WHERE `used`=?;", [$used]);
    $page = paginate(setting('listinvite'), $total);

    if ($total > 0) {

        $invitations = DB::select("SELECT * FROM `invite` WHERE `used`=? ORDER BY `time` DESC LIMIT ".$page['offset'].", ".setting('listinvite').";", [$used]);

        echo '<form action="/admin/invitations?act=del&amp;used='.$used.'&amp;page='.$page['current'].'&amp;uid='.$_SESSION['token'].'" method="post">';

        while ($data = $invitations -> fetch()) {

            echo '<div class="b"><input type="checkbox" name="del[]" value="'.$data['id'].'"> <b>'.$data['hash'].'</b></div>';
            echo '<div>Владелец: '.profile($data['user']).'<br>';

            if (!empty($data['invited'])) {
                echo 'Приглашенный: '.profile($data['invited']).'<br>';
            }
            echo 'Создан: '.dateFixed($data['time']).'<br>';
            echo '</div>';
        }

        echo '<br><input type="submit" value="Удалить выбранное"></form>';

        pagination($page);

        echo 'Всего ключей: <b>'.$total.'</b><br><br>';

    } else {
        showError('Приглашений еще нет!');
    }

    echo '<i class="fa fa-check"></i> <a href="/admin/invitations?act=new">Создать ключи</a><br>';
    echo '<i class="fa fa-key"></i> <a href="/admin/invitations?act=list">Список ключей</a><br>';
break;

############################################################################################
##                                     Создание ключей                                    ##
############################################################################################
case 'new':

    echo '<b><big>Генерация новых ключей:</big></b><br>';
    echo '<div class="form">';
    echo '<form action="/admin/invitations?act=generate&amp;uid='.$_SESSION['token'].'" method="post">';
    echo '<select name="keys">';
    echo '<option value="1">1 ключ</option>';
    echo '<option value="2">2 ключа</option>';
    echo '<option value="3">3 ключа</option>';
    echo '<option value="5">5 ключей</option>';
    echo '<option value="10">10 ключей</option>';
    echo '<option value="20">20 ключей</option>';
    echo '<option value="50">50 ключей</option>';
    echo '</select>	';
    echo '<input type="submit" value="Генерировать"></form></div><br>';

    echo '<b><big>Отправить ключ пользователю:</big></b><br>';
    echo '<div class="form">';
    echo '<form action="/admin/invitations?act=send&amp;uid='.$_SESSION['token'].'" method="post">';
    echo 'Логин пользователя:<br>';
    echo '<input type="text" name="user"><br>';
    echo '<select name="keys">';
    echo '<option value="1">1 ключ</option>';
    echo '<option value="2">2 ключа</option>';
    echo '<option value="3">3 ключа</option>';
    echo '<option value="4">4 ключа</option>';
    echo '<option value="5">5 ключей</option>';
    echo '</select><br>';
    echo '<input type="submit" value="Отправить"></form></div><br>';

    if (isAdmin([101])){
        echo '<b><big>Рассылка ключей:</big></b><br>';
        echo '<div class="form">';
        echo 'Разослать ключи активным пользователям:<br>';
        echo '<form action="/admin/invitations?act=mailing&amp;uid='.$_SESSION['token'].'" method="post">';
        echo '<input type="submit" value="Разослать"></form></div><br>';
    }

    echo '<i class="fa fa-arrow-circle-left"></i> <a href="/admin/invitations">Вернуться</a><br>';
break;

############################################################################################
##                                       Список ключей                                    ##
############################################################################################
case 'list':
    $invitations = DB::select("SELECT hash FROM `invite` WHERE `user`=? AND `used`=? ORDER BY `time` DESC;", [getUser('login'), 0]);
    $invite = $invitations -> fetchAll(PDO::FETCH_COLUMN);
    $total = count($invite);

    if ($total > 0){
        echo 'Всего ваших ключей: '.$total.'<br>';
        echo '<textarea cols="25" rows="10">'.implode(', ', $invite).'</textarea><br><br>';
    } else {
        showError('Ошибка! Нет ваших пригласительных ключей!');
    }
    echo '<i class="fa fa-arrow-circle-left"></i> <a href="/admin/invitations">Вернуться</a><br>';
break;

############################################################################################
##                                Отправка ключей в приват                                ##
############################################################################################
case 'send':

    $uid = (isset($_GET['uid'])) ? check($_GET['uid']) : '';
    $keys = (isset($_POST['keys'])) ? abs(intval($_POST['keys'])) : 1;
    $user = (isset($_REQUEST['user'])) ? check($_REQUEST['user']) : '';

    if ($uid == $_SESSION['token']) {
        if (getUser($user)) {

            $dbr = DB::run() -> prepare("INSERT INTO `invite` (hash, `user`, `time`) VALUES (?, ?, ?);");

            $listkeys = [];

            for($i = 0; $i < $keys; $i++) {
                $key = str_random(rand(12, 15));
                $dbr -> execute($key, $user, SITETIME);
                $listkeys[] = $key;
            }

            $text = 'Вы получили пригласительные ключи в количестве '.count($listkeys).'шт.'.PHP_EOL.'Список ключей: '.implode(', ', $listkeys).PHP_EOL.'С помощью этих ключей вы можете пригласить ваших друзей на этот сайт!';
            sendPrivate($user, getUser('login'), $text);

            setFlash('success', 'Ключи успешно отправлены!');
            redirect("/admin/invitations");

        } else {
            showError('Ошибка! Не найден пользователь с заданным логином!');
        }
    } else {
        showError('Ошибка! Неверный идентификатор сессии, повторите действие!');
    }

    echo '<i class="fa fa-arrow-circle-left"></i> <a href="/admin/invitations?act=new">Вернуться</a><br>';
break;

############################################################################################
##                                Отправка ключей в приват                                ##
############################################################################################
case 'mailing':

    $uid = (isset($_GET['uid'])) ? check($_GET['uid']) : '';

    if ($uid == $_SESSION['token']) {
        if (isAdmin([101])){

            $query = DB::select("SELECT `login` FROM `users` WHERE `timelastlogin`>?;", [SITETIME - (86400 * 7)]);
            $users = $query->fetchAll(PDO::FETCH_COLUMN);

            $users = array_diff($users, [getUser('login')]);
            $total = count($users);

            // Рассылка сообщений с подготовкой запросов
            if ($total>0){

                $text = 'Поздравляем! Вы получили пригласительный ключ'.PHP_EOL.'Ваш ключ: %s'.PHP_EOL.'С помощью этого ключа вы можете пригласить вашего друга на этот сайт!';

                $updateusers = DB::run() -> prepare("UPDATE `users` SET `newprivat`=`newprivat`+1 WHERE `login`=? LIMIT 1;");
                $insertprivat = DB::run() -> prepare("INSERT INTO `inbox` (`user`, `author`, `text`, `time`) VALUES (?, ?, ?, ?);");
                $dbr = DB::run() -> prepare("INSERT INTO `invite` (hash, `user`, `time`) VALUES (?, ?, ?);");

                foreach ($users as $user){
                    $key = str_random(rand(12, 15));
                    $updateusers -> execute($user);
                    $insertprivat -> execute($user, getUser('login'), sprintf($text, $key), SITETIME);
                    $dbr -> execute($key, $user, SITETIME);
                }

                setFlash('success', 'Ключи успешно разосланы! (Отправлено: '.$total.')');
                redirect("/admin/invitations");

            } else {
                showError('Ошибка! Отсутствуют получатели ключей!');
            }
        } else {
            showError('Ошибка! Рассылать ключи может только администрация!');
        }
    } else {
        showError('Ошибка! Неверный идентификатор сессии, повторите действие!');
    }

    echo '<i class="fa fa-arrow-circle-left"></i> <a href="/admin/invitations?act=new">Вернуться</a><br>';
break;
############################################################################################
##                                    Генерация ключей                                    ##
############################################################################################
case 'generate':

    $uid = (isset($_GET['uid'])) ? check($_GET['uid']) : '';
    $keys = (isset($_POST['keys'])) ? abs(intval($_POST['keys'])) : 0;

    if ($uid == $_SESSION['token']) {
        if (!empty($keys)) {

            $dbr = DB::run() -> prepare("INSERT INTO `invite` (hash, `user`, `time`) VALUES (?, ?, ?);");

            for($i = 0; $i < $keys; $i++) {
                $key = str_random(rand(12, 15));
                $dbr -> execute($key, getUser('login'), SITETIME);
            }

            setFlash('success', 'Ключи успешно сгенерированы!');
            redirect("/admin/invitations");

        } else {
            showError('Ошибка! Не указано число генерируемых ключей!');
        }
    } else {
        showError('Ошибка! Неверный идентификатор сессии, повторите действие!');
    }

    echo '<i class="fa fa-arrow-circle-left"></i> <a href="/admin/invitations?act=new">Вернуться</a><br>';
break;

############################################################################################
##                                    Удаление ключей                                     ##
############################################################################################
case 'del':

    $uid = (isset($_GET['uid'])) ? check($_GET['uid']) : '';
    $del = (isset($_REQUEST['del'])) ? intar($_REQUEST['del']) : 0;

    if ($uid == $_SESSION['token']) {
        if (!empty($del)) {

            $del = implode(',', $del);

            DB::delete("DELETE FROM `invite` WHERE `id` IN (".$del.");");

            setFlash('success', 'Выбранные ключи успешно удалены!');
            redirect("/admin/invitations?used=$used&page=$page");

        } else {
            showError('Ошибка! Отсутствуют выбранные ключи!');
        }
    } else {
        showError('Ошибка! Неверный идентификатор сессии, повторите действие!');
    }

    echo '<i class="fa fa-arrow-circle-left"></i> <a href="/admin/invitations">Вернуться</a><br>';
break;

endswitch;

    echo '<i class="fa fa-wrench"></i> <a href="/admin">В админку</a><br>';

} else {
    redirect("/");
}

view(setting('themes').'/foot');
