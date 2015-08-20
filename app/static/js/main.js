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

        // удобные функции для форм
        var formHelper = {
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
            }
        };

        // обработка ошибок ajax
        // TODO: handle other + 390 (redirect) + beautiful messages about
        $(document).ajaxError(function (event, xhr, settings, error) {
            if (xhr.status === 0) {
                // TODO
                console.log('status === 0', event, xhr, settings, error);
            } else if (xhr.status == 390) {
                if ($.type(xhr.responseJSON) === 'string') {
                    window.location = xhr.responseJSON;
                } else {
                    Materialize.toast('Попробуйте обновить страницу (F5)', 5000);
                }
            } else if (xhr.status == 404) {
                // TODO
                console.log(404, event, xhr, settings, error);
            } else if (xhr.status == 503) {
                // TODO
                console.log(503, event, xhr, settings, error);
            } else if (error === 'parsererror') {
                // TODO
                console.log('parsererror', event, xhr, settings, error);
            } else if (error === 'timeout') {
                // TODO
                console.log('timeout', event, xhr, settings, error);
            } else if (error === 'abort') {
                // TODO
                console.log('abort', event, xhr, settings, error);
            } else {
                // TODO
                console.log('uncaught exception', event, xhr, settings, error);
            }
        });

        // TODO: перенести эти переменные в полноценные компоненты
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
        // контейнер заказов для исполнения
        var $ordersList = $('#order-list-fetched');
        // форма перехода к более старым заказам
        var $fetchMoreForm = $('#go-to-older-orders-form');
        // форма добавления заказа
        var $loginForm = $('#login-form');

        // обработка создания заказа
        (function () {
            if ($orderForm.length) {
                var $createdOrders = $('#created-orders');
                var orderTemplate = Handlebars.compile($("#created-orders-new").html());

                $orderForm.validate($.extend({}, defaults.validate, {
                    rules: {description: {notempty: {}}},
                    submitHandler: function (form) {
                        var $form = $(form);

                        $form.ajaxSubmit({
                            beforeSubmit: function () {
                                formHelper.stateAjax(form);
                            }
                        }).data('jqxhr')
                            .done(function (data, status, xhr) {
                                // добавленный заказ
                                if (data.createdOrder && !$.isEmptyObject(data.createdOrder)) {
                                    // TODO: перенести toast и прочие подобные в компонент уведомлений
                                    Materialize.toast('Заказ на сумму ' + data.createdOrder.price_dollar +
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
                                    $.each(data.createErrors, function (idx, err) {
                                        Materialize.toast('Ошибка: ' + err, 10000);
                                    });
                                }
                            })
                            .always(function (xhr, status) {
                                formHelper.stateNormal(form);
                            });
                    }
                }));
            }
        })();

        // обработка исполнения заказов
        (function () {
            $ordersList.on('submit', 'form[data-form="execute-order"]', function () {
                var form = this;
                var $form = $(form);
                var id = $(form).find('[name="id"]').val();
                var $li = $('#order-item-' + id);

                $form.ajaxSubmit({
                    beforeSubmit: function () {
                        formHelper.stateAjax(form);
                    }
                }).data('jqxhr')
                    .done(function (data, status, xhr) {
                        console.log(data);

                        if (data.success) {
                            if (typeof data.balance !== 'undefined' && data.balance !== false) {
                                Materialize.toast('Успешно! Ваш баланс: ' + data.balance, 6000);
                                $balanceHandler.text(data.balance);
                            } else {
                                Materialize.toast('Не удалось получить баланс', 6000);
                            }

                            if ($li.length) {
                                $li.find(':submit').remove();
                                $li.fadeOut(200, function () {
                                    $(this).remove();
                                });
                            }
                        }

                        if (data.error) {
                            Materialize.toast(data.error, 6000);
                        }
                    })
                    .always(function (xhr, status) {
                        formHelper.stateNormal(form);
                    });

                return false;
            });
        })();

        // обработка добавления новых заказов
        (function () {
            if ($fetchMoreForm.length) {
                var fetchedTemplate = Handlebars.compile($("#new-fetched-order").html());

                $fetchMoreForm.on('submit', function () {
                    var form = this;
                    var $form = $(form);
                    var $fromField = $form.find('[name="from"]');
                    var $fromRandField = $form.find('[name="fromRand"]');

                    $form.ajaxSubmit({
                        beforeSubmit: function () {
                            formHelper.stateAjax(form);
                        }
                    }).data('jqxhr')
                        .done(function (data, status, xhr) {
                            console.log(data);

                            // обновляем данные полей для перехода к следующим записям
                            $fromField.val(data.next);
                            $fromRandField.val(data.nextRand);

                            // показываем полученные записи
                            var fetched = $.map(data.orders, function (order) {
                                return fetchedTemplate({order: order});
                            });

                            if (fetched.length) {
                                $ordersList.append(fetched);
                            }
                        })
                        .always(function (xhr, status) {
                            formHelper.stateNormal(form);
                        });

                    return false;
                });
            }
        })();

        // обработка формы логина
        (function () {
            if ($loginForm.length) {
                var $errorRow = $loginForm.find('[data-login-error]');

                $loginForm.validate($.extend({}, defaults.validate, {
                    rules: {username: {notempty: {}}},
                    submitHandler: function (form) {
                        var $form = $(form);
                        $form.ajaxSubmit({
                            beforeSubmit: function () {
                                $errorRow.html('');
                                formHelper.stateAjax(form);
                            }
                        }).data('jqxhr')
                            .done(function (data, status, xhr) {
                                // ошибки реквизитов
                                if (data.error) {
                                    $errorRow.text(data.error);
                                }
                            })
                            .always(function (xhr, status) {
                                formHelper.stateNormal(form);
                            });
                    }
                }));
            }
        })();
    });
})(jQuery);
