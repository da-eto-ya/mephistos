<?php
/**
 * Контроллер авторизации.
 */

require_once __DIR__ . '/../require.php';
require_services('request', 'response', 'template');

/**
 * Логин.
 */
function controller_login()
{
    $form = [
        'email' => '',
        'password' => '',
    ];

    if (request_is_post()) {
        // TODO: check post, create request helpers
        $form['email'] = isset($_POST['email']) ? $_POST['email'] : '';
        $form['password'] = isset($_POST['password']) ? $_POST['password'] : '';
    }

    response_send(template_render('login', ['form' => $form]));
}
