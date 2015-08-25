$$ = window.$$ || {};

(function ($) {
    $(function () {
        // валидатор для непустых полей
        $.validator.addMethod("notempty", function (value, element) {
            return this.optional(element) || /\S/.test(value);
        }, "Пожалуйста, укажите не пустое значение");

        $$.forms = {};

        // значения по умолчанию
        $$.forms.defaults = {
            validate: {
                validClass: 'valid',
                errorClass: 'invalid'
            }
        };

        // удобные функции для форм
        $$.forms.helper = {
            stateAjax: function (form) {
                var $form = $(form);
                $form.find('[data-progress]').css('visibility', 'visible');
                $form.find(':submit').prop('disabled', true);
                $form.find(':input:not([readonly="readonly"])').attr('data-ajax-submit', 'progress').prop('readonly', true);
            },
            stateNormal: function (form) {
                var $form = $(form);
                $form.find(':submit').prop('disabled', false);
                $form.find('[data-progress]').css('visibility', 'hidden');
                $form.find(':input[data-ajax-submit="progress"]').attr('data-ajax-submit', '').prop('readonly', false);
            },
            populate: function (form, data) {
                var $form = $(form);
                $.each(data, function (name, value) {
                    $form.find('[name="' + name + '"]').val(value);
                });
            },
            showErrors: function (form, errors) {
                var $validator = $(form).validate();
                var err = {};

                $.each(errors, function (name, errors) {
                    if ($.isArray(errors)) {
                        err[name] = errors.join('<br>');
                    }
                });

                $validator.showErrors(err);
            },
            ajaxSubmit: function (form, success, before) {
                success = success || function () {
                    };
                before = before || function () {
                    };

                return $(form).ajaxSubmit({
                    beforeSubmit: before
                }).data('jqxhr')
                    .done(success)
                    .always(function () {
                        formHelper.stateNormal(form);
                    });
            }
        };
    });
})(jQuery);
