define(function(require) {
    'use strict';

    var LineItemFormSubmitView;
    var BaseView = require('oroui/js/app/views/base/view');
    var mediator = require('oroui/js/mediator');
    var __ = require('orotranslation/js/translator');
    var $ = require('jquery');

    LineItemFormSubmitView = BaseView.extend({
        optionNames: BaseView.prototype.optionNames.concat([
            'successMessage'
        ]),

        events: {
            'submit form': '_saveChanges'
        },

        successMessage: __('oro.shoppinglist.line_item_save.flash.success'),

        /**
         * @inheritDoc
         */
        constructor: function LineItemFormSubmitView() {
            LineItemFormSubmitView.__super__.constructor.apply(this, arguments);
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
                    mediator.execute('showMessage', 'success', this.successMessage);
                    mediator.trigger('shopping-list:line-items:update-response', {}, {});
                }.bind(this),
                complete: function() {
                    mediator.execute('hideLoading');
                }
            });

            return false;
        }
    });

    return LineItemFormSubmitView;
});
