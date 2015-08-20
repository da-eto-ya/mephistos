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
            },
            ajaxSubmit: function (form, success, before) {
                success = success || function () {};
                before = before || function () {};

                return $(form).ajaxSubmit({
                    beforeSubmit: before
                }).data('jqxhr')
                    .done(success)
                    .always(function () {
                        formHelper.stateNormal(form);
                    });
            }
        };

        // компонент уведомлений
        var megaphone = {
            info: function (text) {
                Materialize.toast(text, 7000);
            },
            alert: function (text) {
                Materialize.toast(text, 7000, 'red darken-2 text-white');
            }
        };

        // обработка ошибок ajax
        $(document).ajaxError(function (event, xhr, settings, error) {
            if (xhr.status === 0) {
                megaphone.alert('Непредвиденная ошибка');
                megaphone.info('Попробуйте обновить страницу');
            } else if (xhr.status == 390) {
                if ($.type(xhr.responseJSON) === 'string') {
                    window.location = xhr.responseJSON;
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
                            .done(function (data) {
                                // добавленный заказ
                                if (data.createdOrder && !$.isEmptyObject(data.createdOrder)) {
                                    megaphone.info('Заказ на сумму ' + data.createdOrder.price_dollar + ' успешно добавлен');

                                    var orderHtml = orderTemplate({
                                        customer: currentUserInfo,
                                        order: data.createdOrder
                                    });
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

        // обработка исполнения заказов
        (function () {
            $ordersList.on('submit', 'form[data-form="execute-order"]', function () {
                var form = this;
                var id = $(form).find('[name="id"]').val();
                var $li = $('#order-item-' + id);

                var executeSuccess = function (data) {
                    if (data.success) {
                        if (typeof data.balance !== 'undefined' && data.balance !== false) {
                            megaphone.info('Успешно! Ваш баланс: ' + data.balance);
                            $balanceHandler.text(data.balance);
                        } else {
                            megaphone.alert('Не удалось получить баланс');
                        }

                        if ($li.length) {
                            $li.find(':submit').remove();
                            $li.fadeOut(200, function () {
                                $(this).remove();
                            });
                        }
                    }

                    if (data.error) {
                        megaphone.error(data.error);
                    }
                };

                formHelper.ajaxSubmit(form, executeSuccess);

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
                        .done(function (data) {
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
                        .always(function () {
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
                            .done(function (data) {
                                // ошибки реквизитов
                                if (data.error) {
                                    $errorRow.text(data.error);
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
