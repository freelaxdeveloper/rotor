<?php
view(setting('themes').'/index');

if (isset($_GET['act'])) {
    $act = check($_GET['act']);
} else {
    $act = 'index';
}

//show_title('Исправительная');

if (isUser()) {
    switch ($action):
    ############################################################################################
    ##                                    Главная страница                                    ##
    ############################################################################################
        case "index":

            echo 'Если вы не злостный нарушитель, но по какой-то причине получили строгое нарушение и хотите от него избавиться - тогда вы попали по адресу.<br>';
            echo 'Здесь самое лучшее место, чтобы встать на путь исправления<br><br>';
            echo 'Снять нарушение можно раз в месяц при условии, что с вашего последнего бана вы не нарушали правил и были добросовестным участником сайта<br>';
            echo 'Также вы должны будете выплатить банку штраф в размере '.plural(100000, setting('moneyname')).'<br>';
            echo 'Если с момента вашего последнего бана прошло менее месяца или у вас нет на руках суммы для штрафа, тогда строгое нарушение снять не удастся<br><br>';
            echo 'Общее число строгих нарушений: <b>'.user('totalban').'</b><br>';

            $daytime = round(((SITETIME - user('timelastban')) / 3600) / 24);

            if (user('timelastban') > 0 && user('totalban') > 0) {
                echo 'Суток прошедших с момента последнего нарушения: <b>'.$daytime.'</b><br>';
            } else {
                echo 'Дата последнего нарушения не указана<br>';
            }

            echo 'Денег на руках: <b>'.plural(user('money'), setting('moneyname')).'</b><br><br>';

            if (user('totalban') > 0 && $daytime >= 30 && user('money') >= 100000) {
                echo '<i class="fa fa-check"></i> <b><a href="/razban?act=go">Снять нарушение</a></b><br>';
                echo 'У вас имеется возможность снять нарушение<br><br>';
            } else {
                echo '<b>Вы не можете снять нарушение</b><br>';
                echo 'Возможно у вас нет нарушений, не прошло еще 30 суток или недостаточная сумма на счете<br><br>';
            }
        break;

        ############################################################################################
        ##                                   Снятие нарушений                                     ##
        ############################################################################################
        case "go":

            $daytime = round(((SITETIME - user('timelastban')) / 3600) / 24);
            if (user('totalban') > 0 && $daytime >= 30 && user('money') >= 100000) {
                DB::run() -> query("UPDATE users SET timelastban=?, totalban=totalban-1, money=money-? WHERE login=?", [SITETIME, 100000, getUsername()]);

                echo 'Нарушение успешно списано, с вашего счета списано <b>'.plural(100000, setting('moneyname')).'</b><br>';
                echo 'Следующее нарушение вы сможете снять не ранее чем через 30 суток<br><br>';
            } else {
                echo '<b>Вы не можете снять нарушение</b><br>';
                echo 'Возможно у вас нет нарушений, не прошло еще 30 суток или недостаточная сумма на счете<br><br>';
            }

            echo '<i class="fa fa-arrow-circle-left"></i> <a href="/razban">Вернуться</a><br>';
        break;

    endswitch;

} else {
    showError('Для снятия нарушения необходимо авторизоваться');
}

view(setting('themes').'/foot');
