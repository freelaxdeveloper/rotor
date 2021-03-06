<?php

namespace App\Controllers\Admin;

use App\Classes\Request;
use App\Classes\Validator;
use App\Models\Setting;
use App\Models\User;

class SettingController extends AdminController
{
    /**
     * Конструктор
     */
    public function __construct()
    {
        parent::__construct();

        if (! isAdmin(User::BOSS)) {
            abort(403, 'Доступ запрещен!');
        }
    }

    /**
     * Главная страница
     */
    public function index()
    {
        $act = check(Request::input('act', 'main'));

        if (! in_array($act, Setting::getActions())) {
            abort('default', 'Недопустимая страница!');
        }

        if (Request::isMethod('post')) {

            $sets  = check(Request::input('sets'));
            $mods  = check(Request::input('mods'));
            $token = check(Request::input('token'));

            $validator = new Validator();
            $validator->equal($token, $_SESSION['token'], ['msg' => 'Неверный идентификатор сессии, повторите действие!'])
                ->notEmpty($sets, ['sets' => 'Ошибка! Не переданы настройки сайта']);

            foreach ($sets as $name => $value) {
                $validator->length($sets[$name], 1, 255, ['sets['.$name.']' => 'Поле '. check($name) .' обязательно для заполнения']);
            }

            if ($validator->isValid()) {

                foreach ($sets as $name => $value) {
                    if (isset($mods[$name])) {
                        $value *= $mods[$name];
                    }

                    Setting::query()->where('name', $name)->update(['value' => $value]);
                }

                saveSetting();

                setFlash('success', 'Настройки сайта успешно изменены!');
                redirect('/admin/setting?act=' . $act);
            } else {
                setInput(Request::all());
                setFlash('danger', $validator->getErrors());
            }
        }

        $settings = Setting::query()->pluck('value', 'name')->all();

        return view('admin/setting/index', compact('settings', 'act'));
    }
}
