<h3>Защита / Безопасность</h3>

<form action="/admin/setting?act=protect" method="post">
    <input type="hidden" name="token" value="{{ $_SESSION['token'] }}">

    <div class="form-group{{ hasError('sets[captcha_symbols]') }}">
        <label for="captcha_symbols">Допустимые символы captcha [a-z0-9]:</label>
        <input pattern="[a-z0-9]+" type="text" class="form-control" id="captcha_symbols" name="sets[captcha_symbols]" maxlength="30" value="{{ getInput('sets[captcha_symbols]', $settings['captcha_symbols']) }}" required>
        {!! textError('sets[captcha_symbols]') !!}
    </div>

    <div class="form-group{{ hasError('sets[captcha_maxlength]') }}">
        <label for="captcha_maxlength">Максимальное количество символов [4-6]:</label>
        <input type="number" min="4" max="6" class="form-control" id="captcha_maxlength" name="sets[captcha_maxlength]" maxlength="1" value="{{ getInput('sets[captcha_maxlength]', $settings['captcha_maxlength']) }}" required>
        {!! textError('sets[captcha_maxlength]') !!}
    </div>

    <div class="form-group{{ hasError('sets[captcha_angle]') }}">
        <label for="captcha_angle">Поворот букв [0-30]:</label>
        <input type="number" min="0" max="30" class="form-control" id="captcha_angle" name="sets[captcha_angle]" maxlength="2" value="{{ getInput('sets[captcha_angle]', $settings['captcha_angle']) }}" required>
        {!! textError('sets[captcha_angle]') !!}
    </div>

    <div class="form-group{{ hasError('sets[captcha_offset]') }}">
        <label for="captcha_offset">Амплитуда колебаний символов [0-10]:</label>
        <input type="number" min="0" max="10" class="form-control" id="captcha_offset" name="sets[captcha_offset]" maxlength="2" value="{{ getInput('sets[captcha_offset]', $settings['captcha_offset']) }}" required>
        {!! textError('sets[captcha_offset]') !!}
    </div>

    <div class="form-check">
        <label class="form-check-label">
            <input type="hidden" value="0" name="sets[captcha_distortion]">
            <input name="sets[captcha_distortion]" class="form-check-input" type="checkbox" value="1"{{ getInput('sets[captcha_distortion]', $settings['captcha_distortion']) ? ' checked' : '' }}>
            Искажение
        </label>
    </div>

    <div class="form-check">
        <label class="form-check-label">
            <input type="hidden" value="0" name="sets[captcha_interpolation]">
            <input name="sets[captcha_interpolation]" class="form-check-input" type="checkbox" value="1"{{ getInput('sets[captcha_interpolation]', $settings['captcha_interpolation']) ? ' checked' : '' }}>
            Размытие
        </label>
    </div>

    <button class="btn btn-primary">Сохранить</button>
</form>
