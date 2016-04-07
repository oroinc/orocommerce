define(function(require) {
    'use strict';

    var RadioInputWidget;
    var $ = require('jquery');
    var AbstractInputWidget = require('oroui/js/app/views/input-widget/abstract');

    RadioInputWidget = AbstractInputWidget.extend({
        widgetFunction: function() {
            this.$el.on('change', function(event) {
                var $element = $(this);
                var inputName = $element.attr('name');

                if ($element.is(':checked')) {
                    $('input[type="radio"][name="' + inputName + '"]')
                        .removeProp('checked')
                        .closest('label').removeClass('checked');

                    $element.prop('checked', 'checked');
                    $element.closest('label').addClass('checked');
                }
            });
        },

        findContainer: function() {
            this.$container = this.$el.closest('label');
        }
    });

    return RadioInputWidget;
});
