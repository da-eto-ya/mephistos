(function ($) {
    $(function () {
        // форма добавления заказа
        var $commissionForm = $('#settings-commission-form');
        // сообщения
        var megaphone = $$.megaphone;
        // хелпер для форм
        var formHelper = $$.forms.helper;
        // умолчания для валидации
        var defaultValidate = $$.forms.defaults.validate;

        // обработка создания заказа
        (function () {
            if ($commissionForm.length) {
                $commissionForm.validate($.extend({}, defaultValidate, {
                    rules: {description: {notempty: {}}},
                    submitHandler: function (form) {
                        var $form = $(form);

                        $form.ajaxSubmit({
                            beforeSubmit: function () {
                                formHelper.stateAjax(form);
                            }
                        }).data('jqxhr')
                            .done(function (data) {
                                // форма
                                if (typeof data.commission !== 'undefined') {
                                    formHelper.populate(form, {commission: data.commission});
                                }

                                // сообщение о сохранённом значении
                                if (data.success) {
                                    megaphone.info('Комиссия успешно сохранена!');
                                }

                                // ошибки
                                if (data.errors && !$.isEmptyObject(data.errors)) {
                                    $.each(data.errors, function (idx, err) {
                                        megaphone.alert(err);
                                    });
                                }
                            })
                            .always(function () {
                                formHelper.stateNormal(form);
                            });
                    }
                }));
            }
        })();
    });
})(jQuery);
