define(function (require) {
    'use strict';

    var RadioInputWidget;
    var AbstractInputWidget = require('oroui/js/app/views/input-widget/abstract');

    RadioInputWidget = AbstractInputWidget.extend({
        widgetFunction: function () {
            this.$el.on('change', function (event) {
                var $element = $(this);
                var inputName = $element.attr('name');

                if ($element.attr('checked') !== 'checked' || typeof $element.attr('checked') === 'undefined') {
                    $('input[type="radio"][name="' + inputName + '"]').attr('checked', false)
                        .closest('label').removeClass('checked');

                    $element.attr('checked', true);
                    $element.closest('label').addClass('checked');
                }
            });
        },


        findContainer: function () {
            this.$container = this.$el.closest('label');
        }
    });

    return RadioInputWidget;
});
