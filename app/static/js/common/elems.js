$$ = window.$$ || {};

(function ($) {
    $(function () {
        $$.elems = {};

        // строка навигации
        $$.elems.navCollapse = $('.button-collapse');
        $$.elems.navCollapse.sideNav();

        // TODO: перенести эти переменные в полноценные компоненты
        // информация о текущем пользователе
        $$.elems.currentUserInfo = {};
        $$.elems.navUserInfo = $('#nav-user-info');

        if ($$.elems.navUserInfo.length) {
            $$.elems.currentUserInfo = {
                avatar: $$.elems.navUserInfo.data('avatar'),
                username: $$.elems.navUserInfo.data('username')
            };
        }

        // строка баланса
        $$.elems.balanceHandler = $('#nav-balance-handler');
    });
})(jQuery);
