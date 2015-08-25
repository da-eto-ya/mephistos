(function ($) {
    $(function () {
        // форма добавления заказа
        var $orderForm = $('#order-form');
        // контейнер заказов для исполнения
        var $ordersList = $('#order-list-fetched');
        // форма перехода к более старым заказам
        var $fetchMoreForm = $('#go-to-older-orders-form');
        // форма входа
        var $loginForm = $('#login-form');
        // сообщения
        var megaphone = $$.megaphone;
        // инфа о текущем пользователе
        var currentUserInfo = $$.elems.currentUserInfo;
        // контейнер баланса
        var $balanceHandler = $$.elems.balanceHandler;
        // хелпер для форм
        var formHelper = $$.forms.helper;
        // умолчания для валидации
        var defaultValidate = $$.forms.defaults.validate;

        // обработка создания заказа
        (function () {
            if ($orderForm.length) {
                var $createdOrders = $('#created-orders');
                var orderTemplate = Handlebars.compile($("#created-orders-new").html());

                $orderForm.validate($.extend({}, defaultValidate, {
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

                $loginForm.validate($.extend({}, defaultValidate, {
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
