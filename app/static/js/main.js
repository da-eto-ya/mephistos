(function ($) {
    $(function () {
        // navigation
        $('.button-collapse').sideNav();

        // counters
        $(document).ready(function () {
            $('textarea[maxlength]').each(function (idx, elem) {
                var $elem = $(elem);
                $elem.attr('length', $elem.attr('maxlength'));
            });
            $('textarea[length]').characterCounter();
        });
    });
})(jQuery);
