<?php
/**
 * Библиотека валидаторов.
 */

require_once __DIR__ . '/../require.php';
require_services('log', 'db');

/**
 * Значение не пусто.
 *
 * @param $value
 * @return bool
 */
function validate_required($value)
{
    return !empty($value);
}

/**
 * Значение соответствует регулярному выражению.
 *
 * @param string $value
 * @param string $regex
 * @return bool
 */
function validate_regex($value, $regex = '/^\w+$/')
{
    return (bool) preg_match($regex, $value);
}

/**
 * Длина значения не более указанной.
 *
 * @param string $value
 * @param int    $length
 * @param string $encoding
 * @return bool
 */
function validate_max_length($value, $length = 0, $encoding = 'UTF-8')
{
    return mb_strlen((string) $value, $encoding) <= (int) $length;
}

/**
 * Значение есть в массиве.
 *
 * @param mixed $value
 * @param array $allowed массив разрешённых значений
 * @return bool
 */
function validate_in_array($value, array $allowed = [])
{
    return in_array($value, $allowed);
}

/**
 * Дата в формате APP_DB_DATE_FORMAT ('Y-m-d H:i:s' для MySQL).
 *
 * @param mixed $value
 * @return bool
 */
function validate_db_datetime($value)
{
    $value = (string) $value;
    $dt = date_create_from_format(APP_DB_DATE_FORMAT, $value);

    if (false === $dt || date_format($dt, APP_DB_DATE_FORMAT) !== $value) {
        return false;
    }

    return true;
}

/**
 * Значение имеет целый тип.
 *
 * @param mixed $value
 * @return bool
 */
function validate_is_int($value)
{
    return is_int($value);
}

/**
 * Значение лежит в диапазоне [$from, $to).
 *
 * @param int|float $value
 * @param int|float $from
 * @param int|float $to
 * @return bool
 */
function validate_range($value, $from = 0, $to = 0)
{
    if (!is_numeric($value)) {
        return false;
    }

    return $from <= $value && $value < $to;
}

/**
 * Валидация массива с указанными правилами.
 *
 * @param array $form
 * @param array $rules
 * @param bool  $logUnset логировать поля, которые есть в правилах и нет в значениях
 * @return array массив ошибок
 */
function validate_fields(array $form, array $rules, $logUnset = true)
{
    $errors = [];

    foreach ($rules as $field => $rule) {
        // в форме должно быть соответствующее поле
        if (!isset($form[$field])) {
            if ($logUnset) {
                log_warning("Can't find form field on validation", [$rules, $field]);
            }
            continue;
        }

        $value = $form[$field];
        $fieldErrors = [];

        foreach ($rule as $validator) {
            $properties = (array) $validator;

            // первый параметр всегда имя валидатора
            if (!isset($properties[0])) {
                log_warning("Can't determine validator", [$field, $validator]);
                continue;
            }

            $name = $properties[0];
            $callable = 'validate_' . $name;

            if (!function_exists($callable)) {
                log_warning("Can't find validator", [$field, $validator]);
                continue;
            }

            $params = isset($validator['params']) ? (array) $validator['params'] : [];

            if (!call_user_func_array($callable, array_merge([$value], $params))) {
                $fieldErrors[] = isset($validator['msg']) ? $validator['msg'] : __validate_default_message($name);
            }
        }

        if ($fieldErrors) {
            $errors[$field] = $fieldErrors;
        }
    }

    return $errors;
}

/**
 * Значение по умолчанию для сообщения ошибки указанного валидатора.
 *
 * @internal
 *
 * @param string $name
 * @return string
 */
function __validate_default_message($name)
{
    // TODO: можно перенести в конфиг
    static $_messages = [
        'required' => 'Поле должно быть не пусто',
        'regex' => 'Поле не соответствует формату',
        'max_length' => 'Поле слишком длинное',
        'is_int' => 'Поле должно содержать число',
        'range' => 'Значение слишком большое или слишком маленькое',
        'db_datetime' => 'Поле содержит дату в неверном формате',
    ];
    static $_default = 'Недопустимое значение поля';

    return isset($_messages[$name]) ? $_messages[$name] : $_default;
}
