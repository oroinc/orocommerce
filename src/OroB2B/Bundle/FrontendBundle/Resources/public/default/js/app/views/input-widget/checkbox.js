define(function(require) {
    'use strict';

    var CheckboxInputWidget;
    var AbstractInputWidget = require('oroui/js/app/views/input-widget/abstract');

    CheckboxInputWidget = AbstractInputWidget.extend({
        widgetFunction: function() {
            this.$el.on('change', function(event) {
                var $content = $('[data-checkbox-triggered-content]');
                if ($(this).attr('checked') !== 'checked' || typeof $(this).attr('checked') === 'undefined') {
                    $(this).attr('checked', true);
                    $(this).parent().addClass('checked');
                    $content.show();
                } else {
                    $(this).attr('checked', false);
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
