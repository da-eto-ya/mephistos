<?php
/**
 * Сервис для работы с JWT (JSON Web Token).
 *
 * @todo: возможно, стоит выделить в утилиты
 */

/**
 * Поддерживаемые алгоритмы.
 *
 * @return array
 */
function jwt_algs()
{
    static $_supported = [
        'HS256' => ['hash_hmac', 'SHA256'],
        'HS512' => ['hash_hmac', 'SHA512'],
        'HS384' => ['hash_hmac', 'SHA384'],
        'RS256' => ['openssl', 'SHA256'],
    ];

    return $_supported;
}

/**
 * Проверка поддержки алгоритма.
 *
 * @param string $algo
 * @return bool
 */
function jwt_is_supported_algo($algo)
{
    if (!is_string($algo)) {
        return false;
    }

    return !empty(jwt_algs()[$algo]);
}

/**
 * Декодирование JWT в PHP-массив.
 *
 * @param string       $jwt JWT
 * @param string|array $key ключ шифрования (ключ для симметричного, массив ключей, публичный ключ для асимметричного)
 * @param array        $allowedAlgs алгоритмы, которые можно использовать для проверки
 * @param int          $leeway дрейф времени в секундах для проверки таймстемпов
 * @return array ['success' => bool, 'error' => string, 'payload' => array]
 */
function jwt_decode($jwt, $key, array $allowedAlgs = [], $leeway = 0)
{
    $result = [
        'success' => false,
        'error' => '',
        'payload' => [],
    ];

    $leeway = (is_numeric($leeway) && (int) $leeway >= 0) ? (int) $leeway : 0;

    if (empty($key)) {
        $result['error'] = 'Key may not be empty';

        return $result;
    }

    $segments = explode('.', $jwt);

    if (count($segments) != 3) {
        $result['error'] = 'Wrong number of segments';

        return $result;
    }

    list($headB64, $bodyB64, $cryptB64) = $segments;

    // декодирование заголовков
    $decodeResult = __jwt_json_decode(__jwt_safe_b64decode($headB64));

    if (!$decodeResult['success'] || null === $decodeResult['result']) {
        $result['error'] = 'Invalid header';

        return $result;
    }

    $header = $decodeResult['result'];

    // декодирование payload
    $decodeResult = __jwt_json_decode(__jwt_safe_b64decode($bodyB64));

    if (!$decodeResult['success'] || null === $decodeResult['result']) {
        $result['error'] = 'Invalid claims';

        return $result;
    }

    $payload = $decodeResult['result'];

    // декодирование подписи
    $signature = __jwt_safe_b64decode($cryptB64);

    if (empty($header['alg'])) {
        $result['error'] = 'Empty algorithm';

        return $result;
    }

    $algo = $header['alg'];

    if (!jwt_is_supported_algo($algo)) {
        $result['error'] = 'Algorithm not supported';

        return $result;
    }

    if (!is_array($allowedAlgs) || !in_array($algo, $allowedAlgs)) {
        $result['error'] = 'Algorithm not allowed';

        return $result;
    }

    if (is_array($key)) {
        if (isset($header['kid'])) {
            $key = $key[$header['kid']];
        } else {
            $result['error'] = '"kid" empty, unable to lookup correct key';

            return $result;
        }
    }

    // Проверка подписи
    $verify = __jwt_verify("$headB64.$bodyB64", $signature, $key, $algo);

    if (!$verify['success']) {
        $result['error'] = 'Signature verification failed';

        return $result;
    }

    // Проверка nbf таймстемпа ('Not Before')
    if (isset($payload['iat']) && $payload['nbf'] > (time() + $leeway)) {
        $result['error'] = 'Cannot handle token prior to ' . date('Y-m-d\TH:i:sO', $payload['nbf']);

        return $result;
    }

    // Проверка iat таймстемпа ('Issued At')
    if (isset($payload['iat']) && $payload['iat'] > (time() + $leeway)) {
        $result['error'] = 'Cannot handle token prior to ' . date('Y-m-d\TH:i:sO', $payload['iat']);

        return $result;
    }

    // Проверка exp таймстемпа ('Expired')
    if (isset($payload['exp']) && (time() - $leeway) >= $payload['exp']) {
        $result['error'] = 'Expired token';

        return $result;
    }

    // Всё ок
    $result['success'] = true;
    $result['payload'] = $payload;

    return $result;
}

/**
 * Кодирует PHP-массив в формат JWT, подписывает сообщение.
 *
 * @param array             $payload данные токена
 * @param string|array|null $key ключ шифрования, массив ключей, приватный ключ для асимметричных алгоритмов
 * @param string            $algo алгоритм подписи
 * @param int|string        $keyId id ключа (при использовании массива ключей)
 * @param array             $head массив дополнительных заголовков
 * @return array ['success' => bool, 'error' => string, 'token' => string, токен]
 */
function jwt_encode(array $payload, $key, $algo = 'HS256', $keyId = null, array $head = null)
{
    $result = [
        'success' => false,
        'error' => '',
        'token' => '',
    ];

    $header = ['typ' => 'JWT', 'alg' => $algo];

    if (null !== $keyId) {
        $header['kid'] = $keyId;
    }

    if (!empty($head)) {
        $header = array_merge($head, $header);
    }

    $segments = [];

    foreach ([$header, $payload] as $value) {
        $encodeResult = __jwt_json_encode($value);

        if (!$encodeResult['success']) {
            $result['success'] = false;
            $result['error'] = $encodeResult['error'];

            return $result;
        }

        $segments[] = __jwt_safe_b64encode($encodeResult['json']);
    }

    $signingInput = implode('.', $segments);
    $signatureResult = jwt_sign($signingInput, $key, $algo);

    if (!$signatureResult['success']) {
        $result['success'] = false;
        $result['error'] = $signatureResult['error'];

        return $result;
    }

    $signature = $signatureResult['sign'];

    $segments[] = __jwt_safe_b64encode($signature);
    $result['token'] = implode('.', $segments);
    $result['success'] = true;

    return $result;
}

/**
 * Подпись сообщения (строки) заданным алгоритмом.
 *
 * @param string          $message сообщение
 * @param string|resource $key ключ шифрования
 * @param string          $algo алгоритм подписи
 *
 * @return array ['success' => bool, 'error' => сообщение об ошибке, 'sign' => string, подпись]
 */
function jwt_sign($message, $key, $algo = 'HS256')
{
    $result = [
        'success' => false,
        'error' => '',
        'sign',
    ];

    if (!jwt_is_supported_algo($algo)) {
        $result['error'] = 'Algorithm not supported';

        return $result;
    }

    list($function, $algorithm) = jwt_algs()[$algo];

    switch ($function) {
        case 'hash_hmac':
            $result['sign'] = hash_hmac($algorithm, $message, $key, true);
            $result['success'] = true;
            break;

        case 'openssl':
            $signature = '';
            $result['success'] = openssl_sign($message, $signature, $key, $algorithm);

            if (!$result['success']) {
                $result['error'] = 'OpenSSL unable to sign data';
            } else {
                $result['sign'] = $signature;
            }
            break;

        default:
            $result['success'] = false;
            $result['error'] = 'Unsupported algo';
            break;
    }

    return $result;
}

/**
 * Проверка подписи сообщения.
 *
 * @param string          $message оригинальное сообщение (заголовок и тело)
 * @param string          $signature оригинальная подпись
 * @param string|resource $key ключ шифрования (resource openssl для RS*)
 * @param string          $algo алгоритм шифрования
 * @return array
 *
 * @internal
 */
function __jwt_verify($message, $signature, $key, $algo)
{
    $result = [
        'success' => false,
        'error' => '',
    ];

    if (!jwt_is_supported_algo($algo)) {
        $result['error'] = 'Algorithm not supported';

        return $result;
    }

    list($function, $algorithm) = jwt_algs()[$algo];

    switch ($function) {
        case 'openssl':
            $result['success'] = openssl_verify($message, $signature, $key, $algorithm);

            if (!$result['success']) {
                $result['error'] = "OpenSSL unable to verify data: " . openssl_error_string();
            }

            break;

        case 'hash_hmac':
            $hash = hash_hmac($algorithm, $message, $key, true);
            $result['success'] = hash_equals($signature, $hash);

            if (!$result['success']) {
                $result['error'] = 'Signature mismatch';
            }

            break;

        default:
            $result['success'] = false;
            $result['error'] = 'Unsupported algorithm in config';
            break;
    }

    return $result;
}

/**
 * Декодирование JSON-строки в PHP-значение.
 *
 * @param string $input JSON
 * @return array
 *
 * @internal
 */
function __jwt_json_decode($input)
{
    $success = false;
    $decoded = json_decode($input, true, 512, JSON_BIGINT_AS_STRING);
    $error = json_last_error();

    if ($error) {
        $errorMessage = __jwt_get_json_error_message($error);
    } else if ($decoded === null && $input !== 'null') {
        $errorMessage = 'Null result with non-null input';
    } else {
        $success = true;
        $errorMessage = '';
    }

    return [
        'success' => $success,
        'error' => $errorMessage,
        'result' => $decoded,
    ];
}

/**
 * Кодирование PHP-значения в JSON-строку.
 *
 * @param mixed $input
 * @return string JSON
 *
 * @internal
 */
function __jwt_json_encode($input)
{
    $success = false;
    $json = json_encode($input);
    $error = json_last_error();

    if ($error) {
        $errorMessage = __jwt_get_json_error_message($error);
    } else if ($json === 'null' && $input !== null) {
        $errorMessage = 'Null result with non-null input';
    } else {
        $success = true;
        $errorMessage = '';
    }

    return [
        'success' => $success,
        'error' => $errorMessage,
        'json' => $json,
    ];
}

/**
 * Декодирование из base64 (URL-безопасное).
 *
 * @param string $input
 * @return string
 *
 * @internal
 */
function __jwt_safe_b64decode($input)
{
    $remainder = strlen($input) % 4;

    if ($remainder) {
        $pad = 4 - $remainder;
        $input .= str_repeat('=', $pad);
    }

    return base64_decode(strtr($input, '-_', '+/'));
}

/**
 * Кодирование в base64 (URL-безопасное).
 *
 * @param string $input
 * @return string
 *
 * @internal
 */
function __jwt_safe_b64encode($input)
{
    return str_replace('=', '', strtr(base64_encode($input), '+/', '-_'));
}

/**
 * Сообщение об ошибке JSON.
 *
 * @param $errorCode
 * @return string
 *
 * @internal
 */
function __jwt_get_json_error_message($errorCode)
{
    static $_messages = [
        JSON_ERROR_DEPTH => 'Maximum stack depth exceeded',
        JSON_ERROR_CTRL_CHAR => 'Unexpected control character found',
        JSON_ERROR_SYNTAX => 'Syntax error, malformed JSON',
    ];

    return isset($_messages[$errorCode]) ? $_messages[$errorCode] : 'Unknown JSON error: ' . $errorCode;
}

/**
 * Количество байт в криптостроке.
 *
 * @param string
 * @return int
 *
 * @internal
 */
function __jwt_safe_strlen($str)
{
    if (function_exists('mb_strlen')) {
        return mb_strlen($str, '8bit');
    }

    return strlen($str);
}
