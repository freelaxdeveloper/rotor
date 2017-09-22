<?php

namespace App\Controllers;

use App\Classes\Request;
use App\Classes\Validation;
use App\Models\Flood;
use App\Models\Guest;
use App\Models\User;
use Illuminate\Database\Capsule\Manager as DB;

class BookController extends BaseController
{
    /**
     * Главная страница
     */
    public function index()
    {
        $total = Guest::query()->count();
        $page = paginate(setting('bookpost'), $total);

        $posts = Guest::query()
            ->orderBy('created_at', 'desc')
            ->limit(setting('bookpost'))
            ->offset($page['offset'])
            ->with('user', 'editUser')
            ->get();

        return view('book/index', compact('posts', 'page'));
    }

    /**
     * Добавление сообщения
     */
    public function add()
    {
        $msg   = check(Request::input('msg'));
        $token = check(Request::input('token'));

        $validation = new Validation();
        $validation->addRule('equal', [$token, $_SESSION['token']], ['msg' => 'Неверный идентификатор сессии, повторите действие!'])
            ->addRule('string', $msg, ['msg' => 'Ошибка! Слишком длинное или короткое сообщение!'], true, 5, setting('guesttextlength'))
            ->addRule('bool', Flood::isFlood(), ['msg' => 'Антифлуд! Разрешается отправлять сообщения раз в ' . Flood::getPeriod() . ' секунд!']);

        /* Проерка для гостей */
        if (!getUser() && setting('bookadds')) {
            $protect = check(strtolower(Request::input('protect')));
            $validation->addRule('equal', [$protect, $_SESSION['protect']], ['protect' => 'Проверочное число не совпало с данными на картинке!']);
        } else {
            $validation->addRule('bool', getUser(), ['msg' => 'Для добавления сообщения необходимо авторизоваться']);
        }

        if ($validation->run()) {

            $msg = antimat($msg);

            if (getUser()) {
                $bookscores = (setting('bookscores')) ? 1 : 0;

                $user = User::query()->where('id', getUser('id'));
                $user->update([
                    'allguest' => DB::raw('allguest + 1'),
                    'point'    => DB::raw('point + ' . $bookscores),
                    'money'    => DB::raw('money + 5'),
                ]);
            }

            $username = getUser() ? getUser('id') : 0;

            Guest::query()->create([
                'user_id'    => $username,
                'text'       => $msg,
                'ip'         => getClientIp(),
                'brow'       => getUserAgent(),
                'created_at' => SITETIME,
            ]);

            setFlash('success', 'Сообщение успешно добавлено!');
        } else {
            setInput(Request::all());
            setFlash('danger', $validation->getErrors());
        }

        redirect("/book");
    }

    /**
     * Подготовка к редактированию
     */
    public function edit($id)
    {
        if (! getUser()) {
            abort(403);
        }

        $post = Guest::query()->where('user_id', getUser('id'))->find($id);

        if (! $post) {
            abort('default', 'Ошибка! Сообщение удалено или вы не автор этого сообщения!');
        }

        if ($post['created_at'] + 600 < SITETIME) {
            abort('default', 'Редактирование невозможно, прошло более 10 минут!');
        }

        if (Request::isMethod('post')) {

            $msg   = check(Request::input('msg'));
            $token = check(Request::input('token'));

            $validation = new Validation();
            $validation->addRule('equal', [$token, $_SESSION['token']], ['msg' => 'Неверный идентификатор сессии, повторите действие!'])
                ->addRule('string', $msg, ['msg' => 'Ошибка! Слишком длинное или короткое сообщение!'], true, 5, setting('guesttextlength'));

            if ($validation->run()) {

                $msg = antimat($msg);

                $post->text = $msg;
                $post->edit_user_id = getUser('id');
                $post->updated_at = SITETIME;
                $post->save();

                setFlash('success', 'Сообщение успешно отредактировано!');
                redirect('/book');
            } else {
                setInput(Request::all());
                setFlash('danger', $validation->getErrors());
            }
        }

        return view('book/edit', compact('post'));
    }
}
