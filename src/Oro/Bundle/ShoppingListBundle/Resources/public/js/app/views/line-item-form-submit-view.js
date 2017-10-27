define(function(require) {
    'use strict';

    var LineItemFormSubmitView;
    var BaseView = require('oroui/js/app/views/base/view');
    var mediator = require('oroui/js/mediator');
    var $ = require('jquery');

    LineItemFormSubmitView = BaseView.extend({
        events: {
            'submit form': '_saveChanges'
        },

        _saveChanges: function(e) {
            var $form = $(e.currentTarget);
            var validator = $form.validate();
            if (validator && !validator.form()) {
                return false;
            }

            mediator.execute('showLoading');

            $.ajax({
                type: $form.attr('method'),
                url: $form.attr('action'),
                data: $form.serialize(),
                success: function() {
                    mediator.trigger('shopping-list:line-items:update-response', {}, {});
                },
                complete: function() {
                    mediator.execute('hideLoading');
                }
            });

            return false;
        }
    });

    return LineItemFormSubmitView;
});
