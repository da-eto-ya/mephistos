(function ($) {
    $(function () {
        // строка навигации
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

        // обработка ошибок ajax
        var formHelper = {
            disable: function (form) {
                var $form = $(form);
                $form.find('[data-progress]').css('visibility', 'visible');
                $form.find(':submit').prop('disabled', true);
                $form.find(':input:not([readonly="readonly"])').attr('data-ajax-submit', 'progress').prop('readonly', true);
            },
            enable: function (form) {
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
            }
        };

        // TODO: handle other + 390 (redirect) + beautiful messages about
        $(document).ajaxError(function (event, xhr, settings, error) {
            if (xhr.status === 0) {
                console.log('status === 0', event, xhr, settings, error);
            } else if (xhr.status == 404) {
                console.log(404, event, xhr, settings, error);
            } else if (xhr.status == 503) {
                console.log(503, event, xhr, settings, error);
            } else if (error === 'parsererror') {
                console.log('parsererror', event, xhr, settings, error);
            } else if (error === 'timeout') {
                console.log('timeout', event, xhr, settings, error);
            } else if (error === 'abort') {
                console.log('abort', event, xhr, settings, error);
            } else {
                console.log('uncaught exception', event, xhr, settings, error);
            }
        });

        // информация о текущем пользователе
        var currentUserInfo = {};
        var $navUserInfo = $('#nav-user-info');

        if ($navUserInfo.length) {
            currentUserInfo = {
                avatar: $navUserInfo.data('avatar'),
                username: $navUserInfo.data('username')
            };
        }

        // строка баланса
        var $balanceHandler = $('#nav-balance-handler');

        // форма добавления заказа
        var $orderForm = $('#order-form');

        if ($orderForm.length) {
            var $createdOrders = $('#created-orders');
            var orderTemplate = Handlebars.compile($("#created-orders-new").html());

            $orderForm.validate($.extend({}, defaults.validate, {
                rules: {description: {notempty: {}}},
                submitHandler: function (form) {
                    var $form = $(form);

                    $form.ajaxSubmit({
                        beforeSubmit: function () {
                            formHelper.disable(form);
                        }
                    }).data('jqxhr')
                        .done(function (data, status, xhr) {
                            // добавленный заказ
                            if (data.createdOrder && !$.isEmptyObject(data.createdOrder)) {
                                Materialize.toast('Заказ на сумму ' + data.createdOrder['price_dollar'] +
                                    ' успешно добавлен', 6000);

                                var orderHtml = orderTemplate({customer: currentUserInfo, order: data.createdOrder});
                                $createdOrders.prepend(orderHtml);
                            }

                            // форма
                            if (data.order) {
                                formHelper.populate(form, data.order);
                            }

                            // ошибки уровня формы
                            if (data.errors) {
                                formHelper.showErrors(form, data.errors);
                            }

                            // ошибки не уровня формы
                            if (data.createErrors && !$.isEmptyObject(data.createErrors)) {
                                $.each(data.createErrors, function (err, idx) {
                                    Materialize.toast('Ошибка: ' + err, 6000);
                                });
                            }
                        })
                        .always(function (xhr, status) {
                            formHelper.enable(form);
                        });
                }
            }));
        }

        // форма исполнения заказа
        $('form[data-form="execute-order"]').on('submit', function () {
            var form = this;
            var id = $(form).find('[name="id"]').val();
            var $li = $('#order-item-' + id);

            $(this).ajaxSubmit({
                beforeSubmit: function () {
                    formHelper.disable(form);
                }
            }).data('jqxhr')
                .done(function (data, status, xhr) {
                    if (data.success) {
                        if (typeof data.balance !== 'undefined' && data.balance !== false) {
                            Materialize.toast('Успешно! Ваш баланс: ' + data.balance, 6000);
                            $balanceHandler.html(data.balance);
                        } else {
                            Materialize.toast('Не удалось получить баланс', 6000);
                        }

                        if ($li.length) {
                            $li.find(':submit').remove();
                            $li.fadeOut(200, function() { $(this).remove(); });
                        }
                    }

                    if (data.error) {
                        Materialize.toast(data.error, 6000);
                    }
                })
                .always(function (xhr, status) {
                    formHelper.enable(form);
                });

            return false;
        });
    });
})(jQuery);
