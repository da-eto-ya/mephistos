<?php
/**
 * Сервис аутентификации и идентификации пользователей.
 */

require_once __DIR__ . '/../require.php';
require_services('security', 'jwt', 'request', 'response');
require_modules('repo/users');

/**
 * Устанавливает или возвращает конфигурацию компонента аутентификации.
 *
 * @param array|null $config массив для установки или null для возврата ранее сохранённого значения
 * @return array
 */
function auth_config(array $config = null)
{
    static $_config = [
        // TODO: возможно, стоит перенести secret_key в конфиг безопасности
        'secret_key' => 'oh my secret key...',
        'token_algo' => 'HS256',
        'cookie' => 'auth',
        'domain' => null,
        'expire' => 86400, // 1d
    ];

    if (null !== $config) {
        foreach (['secret_key', 'cookie', 'domain'] as $key) {
            if (isset($config[$key]) && !empty((string) $_config[$key])) {
                $_config[$key] = (string) $config[$key];
            }
        }

        if (isset($config['token_algo']) && jwt_is_supported_algo($config['token_algo'])) {
            $_config['token_algo'] = $config['token_algo'];
        }

        if (isset($config['expire']) && is_int($config['expire'])) {
            $_config['expire'] = $config['expire'];
        }
    }

    return $_config;
}

/**
 * Находит пользователя, подходящего под указанные credentials.
 *
 * @param array $credentials
 * @return mixed false в том случае, если такой пользователь не найден
 *
 * @todo может, отвязаться от модели пользователя и возвращать ID?
 */
function auth_find_user($credentials)
{
    if (empty($credentials['username']) || empty($credentials['password'])) {
        return false;
    }

    $user = repo_users_get_one_by_username($credentials['username']);

    if (!$user || !security_password_verify($credentials['password'], $user['hash'])) {
        return false;
    }

    return $user;
}

/**
 * Обновляет хэш пароля для пользователя.
 *
 * @param int    $uid
 * @param string $password
 * @return bool|int
 */
function auth_rehash_user_password($uid, $password)
{
    return repo_users_update_one($uid, ['hash' => security_password_hash($password)]);
}

/**
 * Генерирует токен уровня сессии.
 *
 * @param int   $uid id пользователя
 * @param array $data доп. данные
 * @return bool
 */
function auth_generate_session_token($uid, array $data = [])
{
    $uid = (int) $uid;

    if (!$uid || $uid <= 0) {
        return false;
    }

    $config = auth_config();
    $now = time();

    $payload = array_merge($data, [
        'sub' => $uid,
        'iat' => $now,
        'nbf' => $now,
        'exp' => $now + $config['expire'],
        'purpose' => 'session',
    ]);

    $tokenResult = jwt_encode($payload, $config['secret_key'], $config['token_algo']);

    if (!$tokenResult['success']) {
        return false;
    }

    return $tokenResult['token'];
}

/**
 * Отправляет токен клиенту.
 *
 * @param string $token
 * @return bool
 */
function auth_identify_session($token)
{
    $token = (string) $token;

    if (!$token) {
        // TODO: log
        return false;
    }

    $config = auth_config();

    return response_set_cookie(
        $config['cookie'],
        $token,
        time() + $config['expire'],
        '/',
        $config['domain'],
        null,
        true
    );
}

/**
 * Генерирует и отправляет токен клиенту.
 *
 * @param int $uid id пользователя
 * @return bool
 */
function auth_start_authorized_session($uid)
{
    return auth_identify_session(auth_generate_session_token($uid));
}

/**
 * Остановить сессию клиента.
 *
 * @return bool
 */
function auth_stop_authorized_session()
{
    return response_remove_cookie(auth_config()['cookie']);
}

/**
 * Получить данные сессии аутентификации.
 *
 * @return mixed|bool
 */
function auth_receive_session_data()
{
    $config = auth_config();
    $token = request_read_cookie($config['cookie']);

    if (!$token) {
        return false;
    }

    $payloadResult = jwt_decode($token, $config['secret_key'], [$config['token_algo']]);

    if (!$payloadResult['success']) {
        // TODO: log
        return false;
    }

    $payload = $payloadResult['payload'];

    if (!is_int($payload['sub']) || !isset($payload['purpose']) || $payload['purpose'] !== 'session') {
        return false;
    }

    return $payload;
}

/**
 * Получить модель текущего пользователя.
 *
 * @param null $sessionData данные сессии (результат auth_receive_session_data)
 * @param bool $force форсировать получение и установку пользователя (не использовать кешированный результат)
 * @return array|bool false, если пользователь не найден
 */
function auth_get_current_user($sessionData = null, $force = false)
{
    static $_user = null;

    if (null === $_user || $force) {
        if (null === $sessionData) {
            $sessionData = auth_receive_session_data();
        }

        if ($sessionData && !empty($sessionData['sub']) && is_int($sessionData['sub'])) {
            $_user = repo_users_get_one_by_id($sessionData['sub']);
        }
    }

    return $_user;
}
