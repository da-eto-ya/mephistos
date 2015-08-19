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

        // форма добавления заказа
        var $orderForm = $('#order-form');

        if ($orderForm.length) {
            var $navUserInfo = $('#nav-user-info');
            var customer = {
                avatar: $navUserInfo.data('avatar'),
                username: $navUserInfo.data('username')
            };
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
                            console.log(data);
                            if (data.createdOrder && !$.isEmptyObject(data.createdOrder)) {
                                Materialize.toast('Заказ на сумму ' + data.createdOrder['price_dollar'] +
                                    ' успешно добавлен', 6000);

                                var orderHtml = orderTemplate({customer: customer, order: data.createdOrder});
                                $createdOrders.prepend(orderHtml);
                            }

                            if (data.order) {
                                formHelper.populate(form, data.order);
                            }

                            if (data.errors) {
                                formHelper.showErrors(form, data.errors);
                            }
                        })
                        .always(function (xhr, status) {
                            formHelper.enable(form);
                        });
                }
            }));
        }
    });
})(jQuery);
