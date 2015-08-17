<?php
/**
 * Хелперы для работы с запросом.
 */

// Источник параметра
/** Брать параметры из $_POST */
const APP_PARAM_FROM_POST = 1;
/** Брать параметры из $_GET */
const APP_PARAM_FROM_GET = 2;
/** Брать параметры из $_POST и, если не найден, из $_GET */
const APP_PARAM_FROM_BOTH = 3;

// Тип обработки параметра
/** Оставить параметр как есть */
const APP_PARAM_RAW = 1;
/** Обрезать пробельные символы в начале и конце значения параметра */
const APP_PARAM_TRIM = 2;
/** Привести параметр к integer */
const APP_PARAM_INT = 3;
/** Привести параметр к float */
const APP_PARAM_FLOAT = 4;
/** Привести параметр к boolean */
const APP_PARAM_BOOL = 5;

/**
 * Получить параметр из переменных запроса
 *
 * @param string $name имя параметра
 * @param null   $default значение по умолчанию, если параметр не найден
 * @param int    $type тип параметра
 * @param int    $source источник параметра
 * @return mixed
 */
function _req($name, $default = null, $type = APP_PARAM_TRIM, $source = APP_PARAM_FROM_BOTH)
{
    $value = null;

    if (APP_PARAM_FROM_BOTH === $source) {
        if (isset($_POST[$name])) {
            $value = $_POST[$name];
        } else if (isset($_GET[$name])) {
            $value = $_GET[$name];
        } else {
            $value = null;
        }
    } else if (APP_PARAM_FROM_POST === $source) {
        $value = isset($_POST[$name]) ? $_POST[$name] : null;
    } else if (APP_PARAM_FROM_GET === $source) {
        $value = isset($_GET[$name]) ? $_GET[$name] : null;
    }

    // не найдена переменная запроса
    if (null === $value) {
        return $default;
    }

    switch ($type) {
        case APP_PARAM_INT:
            $value = (int) $value;
            break;

        case APP_PARAM_BOOL:
            $value = (bool) $value;
            break;

        case APP_PARAM_FLOAT:
            $value = (float) str_replace(',', '.', $value);
            break;

        case APP_PARAM_TRIM:
            $value = trim($value);
            break;

        case APP_PARAM_RAW:
            break;
    }

    return $value;
}

/**
 * Получить параметр из переменных POST
 *
 * @param string $name имя параметра
 * @param null   $default значение по умолчанию, если параметр не найден
 * @param int    $type тип параметра
 * @return mixed
 */
function _p($name, $default = null, $type = APP_PARAM_TRIM)
{
    return _req($name, $default, $type, APP_PARAM_FROM_POST);
}

/**
 * Получить параметр из переменных GET
 *
 * @param string $name имя параметра
 * @param null   $default значение по умолчанию, если параметр не найден
 * @param int    $type тип параметра
 * @return mixed
 */
function _g($name, $default = null, $type = APP_PARAM_TRIM)
{
    return _req($name, $default, $type, APP_PARAM_FROM_GET);
}
