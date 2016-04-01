define(function(require) {
    'use strict';

    var CheckboxInputWidget;
    var $ = require('jquery');
    var AbstractInputWidget = require('oroui/js/app/views/input-widget/abstract');

    CheckboxInputWidget = AbstractInputWidget.extend({
        widgetFunction: function() {
            this.$el.on('change', function(event) {
                var $content = $('[data-checkbox-triggered-content]');
                if ($(this).prop('checked') !== 'checked' || typeof $(this).prop('checked') === 'undefined') {
                    $(this).prop('checked', true);
                    $(this).parent().addClass('checked');
                    $content.show();
                } else {
                    $(this).removeProp('checked');
                    $(this).parent().removeClass('checked');
                    $content.hide();
                }

                event.stopPropagation();
            });
        },

        findContainer: function() {
            this.$container = this.$el.closest('label');
        }
    });

    return CheckboxInputWidget;
});
