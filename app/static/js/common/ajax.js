(function ($) {
    $(function () {
        var megaphone = $$.megaphone;
        // обработка ошибок ajax
        $(document).ajaxError(function (event, xhr, settings, error) {
            if (xhr.status === 0) {
                megaphone.alert('Непредвиденная ошибка');
                megaphone.info('Попробуйте обновить страницу');
            } else if (xhr.status == 418) {
                if ($.type(xhr.responseJSON) === 'string') {
                    setTimeout(function () {
                        document.location.href = xhr.responseJSON;
                    }, 500);
                } else {
                    megaphone.info('Попробуйте обновить страницу');
                }
            } else if (xhr.status == 404) {
                megaphone.alert('Страница не найдена');
                megaphone.info('Попробуйте обновить страницу');
            } else if (xhr.status == 403) {
                megaphone.alert('Доступ запрещён');
                megaphone.info('Попробуйте перелогиниться');
            } else if (400 <= xhr.status && xhr.status < 500) {
                megaphone.alert('Запрос не обработан');
                megaphone.info('Попробуйте обновить страницу');
            } else if (xhr.status == 500) {
                megaphone.alert('Ошибка сервера');
                megaphone.info('Попробуйте ещё раз');
            } else if (xhr.status == 503) {
                megaphone.alert('Сервер на обслуживании');
                megaphone.info('Заходите позже');
            } else if (xhr.status == 504) {
                megaphone.alert('Сервер не ответил');
                megaphone.info('Попробуйте ещё раз');
            } else if (500 <= xhr.status && xhr.status < 600) {
                megaphone.alert('Ошибка сервера');
                megaphone.info('Попробуйте обновить страницу');
            } else if (error === 'parsererror') {
                megaphone.alert('Ошибка браузера');
                megaphone.info('Попробуйте обновить страницу');
            } else if (error === 'timeout') {
                megaphone.alert('Сервер не ответил');
                megaphone.info('Попробуйте ещё раз');
            } else if (error === 'abort') {
                megaphone.alert('Сервер оборвал связь');
                megaphone.info('Попробуйте ещё раз');
            } else {
                megaphone.alert('Произошло что-то странное');
                megaphone.info('Попробуйте ещё раз');
            }
        });
    });
})(jQuery);
