<?php
view(setting('themes').'/index');

if (isset($_GET['act'])) {
    $act = check($_GET['act']);
} else {
    $act = 'index';
}
if (isset($_GET['uz'])) {
    $uz = check($_GET['uz']);
} elseif (isset($_POST['uz'])) {
    $uz = check($_POST['uz']);
} else {
    $uz = "";
}
$page = abs(intval(Request::input('page', 1)));

//show_title('Рейтинг репутации');

switch ($action):
############################################################################################
##                                    Вывод пользователей                                 ##
############################################################################################
    case 'index':

        $total = DB::run() -> querySingle("SELECT count(*) FROM `users`;");
        $page = paginate(setting('avtorlist'), $total);

        if ($total > 0) {

            $queryusers = DB::select("SELECT * FROM `users` ORDER BY `rating` DESC, `login` ASC LIMIT ".$page['offset'].", ".setting('avtorlist').";");

            $i = 0;
            while ($data = $queryusers -> fetch()) {
                ++$i;

                echo '<div class="b">'.($page['offset'] + $i).'. '.userGender($data['login']);

                if ($uz == $data['login']) {
                    echo ' <b><big>'.profile($data['login'], '#ff0000').'</big></b> (Репутация: '.($data['rating']).')</div>';
                } else {
                    echo ' <b>'.profile($data['login']).'</b> (Репутация: '.($data['rating']).')</div>';
                }

                echo '<div>Плюсов: '.$data['posrating'].' / Минусов: '.$data['negrating'].'<br>';
                echo 'Дата регистрации: '.dateFixed($data['joined'], 'j F Y').'</div>';
            }

            pagination($page);

            echo '<div class="form">';
            echo '<b>Поиск пользователя:</b><br>';
            echo '<form action="/authoritylist?act=search&amp;page='.$page['current'].'" method="post">';
            echo '<input type="text" name="uz" value="'.getUser('login').'">';
            echo '<input type="submit" value="Искать"></form></div><br>';

            echo 'Всего пользователей: <b>'.$total.'</b><br><br>';
        } else {
            showError('Пользователей еще нет!');
        }
    break;

    ############################################################################################
    ##                                  Поиск пользователя                                    ##
    ############################################################################################
    case 'search':

        if (!empty($uz)) {
            $queryuser = DB::run() -> querySingle("SELECT `login` FROM `users` WHERE LOWER(`login`)=? LIMIT 1;", [strtolower($uz)]);

            if (!empty($queryuser)) {
                $queryrating = DB::select("SELECT `login` FROM `users` ORDER BY `rating` DESC, `login` ASC;");
                $ratusers = $queryrating -> fetchAll(PDO::FETCH_COLUMN);

                foreach ($ratusers as $key => $ratval) {
                    if ($queryuser == $ratval) {
                        $rat = $key + 1;
                    }
                }
                if (!empty($rat)) {
                    $end = ceil($rat / setting('avtorlist'));

                    setFlash('success', 'Позиция в рейтинге: '.$rat);
                    redirect("/authoritylist?page=$end&uz=$queryuser");
                } else {
                    showError('Пользователь с данным логином не найден!');
                }
            } else {
                showError('Пользователь с данным логином не зарегистрирован!');
            }
        } else {
            showError('Ошибка! Вы не ввели логин пользователя');
        }

        echo '<i class="fa fa-arrow-circle-left"></i> <a href="/authoritylist?page='.$page.'">Вернуться</a><br>';
    break;

endswitch;

view(setting('themes').'/foot');
