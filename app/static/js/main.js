(function ($) {
    $(function () {
        // navigation
        $('.button-collapse').sideNav();

        // валидатор для непустых полей
        $.validator.addMethod("notempty", function (value, element) {
            return this.optional(element) || /\S/.test(value);
        }, "Пожалуйста, укажите не пустое значение");

        // значения по умолчанию
        var defaults = {
            validate: {
                validClass: 'valid',
                errorClass: 'invalid'
            }
        };

        // форма добавления заказа
        var $orderForm = $('#order-form');

        if ($orderForm.length) {
            $orderForm.validate($.extend({}, defaults.validate, {
                rules: {
                    description: {
                        notempty: {}
                    }
                }
            }));
        }
    });
})(jQuery);
