define(function(require) {
    'use strict';

    const BaseView = require('oroui/js/app/views/base/view');
    const mediator = require('oroui/js/mediator');
    const __ = require('orotranslation/js/translator');
    const $ = require('jquery');

    const LineItemFormSubmitView = BaseView.extend({
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
        constructor: function LineItemFormSubmitView(options) {
            LineItemFormSubmitView.__super__.constructor.call(this, options);
        },

        _saveChanges: function(e) {
            const $form = $(e.currentTarget);
            const validator = $form.validate();
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
