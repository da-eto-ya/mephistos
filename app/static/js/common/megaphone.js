// компонент уведомлений
$$ = window.$$ || {};
$$.megaphone = {
    info: function (text) {
        Materialize.toast(text, 7000);
    },
    alert: function (text) {
        Materialize.toast(text, 7000, 'red darken-2 text-white');
    }
};
