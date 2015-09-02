<?php
/**
 * Сервис аутентификации и идентификации пользователей.
 */

require_once __DIR__ . '/../require.php';
require_services('security', 'jwt', 'request', 'response', 'router');
require_repos('users');

/**
 * Устанавливает или возвращает конфигурацию компонента аутентификации.
 *
 * @param array|null $config массив для установки или null для возврата ранее сохранённого значения
 * @return array
 */
function auth_config(array $config = null)
{
    static $_config = [
        // TODO: возможно, стоит перенести auth_key/csrf_key в конфиг безопасности
        'auth_key' => 'oh, my secret key...',
        'csrf_key' => 'oh, my poor csrf key!..',
        'token_algo' => 'HS256',
        'cookie' => 'auth',
        'domain' => null,
        'expire' => 86400, // 1d
    ];

    if (null !== $config) {
        foreach (['auth_key', 'csrf_key', 'cookie', 'domain'] as $key) {
            if (isset($config[$key]) && !empty((string) $config[$key])) {
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

    if (!$user || !security_password_verify($credentials['password'], $user['password_hash'])) {
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
    return repo_users_update_one($uid, ['password_hash' => security_password_hash($password)]);
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

    // для jti совершенно не обязательно использовать криптостойкий алгоритм.
    // это просто id сессии. достаточно того, чтобы была крайне малая вероятность
    // случайного совпадения значений, отвечающих за различные сессии.
    $jti = md5(join(':', [$uid, microtime(), mt_rand()]));

    $payload = array_merge($data, [
        'sub' => $uid,
        'jti' => $jti,
        'iat' => $now,
        'nbf' => $now,
        'exp' => $now + $config['expire'],
        'purpose' => 'session',
    ]);

    $tokenResult = jwt_encode($payload, $config['auth_key'], $config['token_algo']);

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

    return response_write_cookie(
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
    return response_remove_cookie(auth_config()['cookie'], '/', auth_config()['domain']);
}

/**
 * Получить данные сессии аутентификации.
 *
 * @param bool $force форсировать получение данных, не использовать кеш
 * @return bool|mixed
 */
function auth_get_session_data($force = false)
{
    static $_payload = null;

    if (null === $_payload || $force) {
        $config = auth_config();
        $token = request_read_cookie($config['cookie']);

        if (!$token) {
            $_payload = false;

            return false;
        }

        $payloadResult = jwt_decode($token, $config['auth_key'], [$config['token_algo']]);

        if (!$payloadResult['success']) {
            $_payload = false;

            // TODO: log
            return false;
        }

        $_payload = $payloadResult['payload'];
    }

    if ($_payload && isset($_payload['purpose']) && $_payload['purpose'] === 'session') {
        return $_payload;
    }

    return false;
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
            $sessionData = auth_get_session_data($force);
        }

        if ($sessionData && !empty($sessionData['sub']) && is_int($sessionData['sub'])) {
            $_user = repo_users_get_one_by_id($sessionData['sub']);
        }
    }

    return $_user;
}

/**
 * Возвращает URL по умолчанию для пользователя.
 *
 * @param array|null $user
 * @return string
 */
function auth_get_default_url(array $user = null)
{
    if (!$user || !isset($user['role'])) {
        return '/';
    }

    // TODO: move to config level
    $urls = [
        APP_ROLE_EXECUTOR => router_get_path('orders', 'list'),
        APP_ROLE_CUSTOMER => router_get_path('orders', 'create'),
        APP_ROLE_ADMIN => router_get_path('admin', ''),
    ];

    return isset($urls[$user['role']]) ? $urls[$user['role']] : '';
}

/**
 * Разрешён ли авторизованный доступ к ресурсу.
 *
 * @param array $user модель пользователя
 * @param array $roles список разрешённых ролей
 * @return bool
 */
function auth_user_has_role(array $user = null, array $roles = [])
{
    if (!$user || !isset($user['role'])) {
        return false;
    }

    return in_array($user['role'], $roles);
}

/**
 * Получить токен CSRF для действия, определяемого переданными параметрами.
 *
 * @param array      $params уникальные параметры запроса (массив, ключи игнорируются)
 * @param array|null $sessionData данные сессии
 * @return bool|string false в случае отсутствия данных сессии или ошибки
 */
function auth_get_csrf(array $params = [], $sessionData = null)
{
    static $_cache = [];

    if (null === $sessionData) {
        $sessionData = auth_get_session_data();
    }

    if (!$sessionData) {
        return false;
    }

    $params[] = $sessionData['jti'];
    $action = join(':', $params);

    if (!isset($_cache[$action])) {
        $_cache[$action] = hash_hmac('SHA256', $action, auth_config()['csrf_key']);
    }

    return $_cache[$action];
}

/**
 * Проверить, что переданный токен соответствует генерированному для данных параметров.
 *
 * @param string $userToken токен, полученный от пользователя
 * @param array  $params уникальные параметры запроса (массив, ключи игнорируются)
 * @param null   $sessionData данные сессии
 * @return bool true в случае совпадения токенов (валидный запрос)
 */
function auth_validate_csrf($userToken, array $params = [], $sessionData = null)
{
    $generatedToken = auth_get_csrf($params, $sessionData);

    if (false === $generatedToken) {
        return false;
    }

    return hash_equals($generatedToken, $userToken);
}
