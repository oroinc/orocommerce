/*global define*/
define(function(require) {
    'use strict';

    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var $ = require('jquery');
    var mediator = require('oroui/js/mediator');
    var ButtonComponent = require('oroworkflow/js/app/components/button-component');
    var StandardConfirmation = require('oroui/js/standart-confirmation');
    var TransitionHandler = require('oroworkflow/js/transition-handler');
    var t = require('orotranslation/js/translator');
    var ShoppingListMatrixCreateOrderButtonComponent;

    ShoppingListMatrixCreateOrderButtonComponent = ButtonComponent.extend({
        /**
         * @type {Object}
         */
        messages: {
            content: t('oro.shoppinglist.create_order_confirmation.message'),
            title: t('oro.shoppinglist.create_order_confirmation.title'),
            okText: t('oro.shoppinglist.create_order_confirmation.accept_button_title'),
            cancelText: t('oro.shoppinglist.create_order_confirmation.cancel_button_title')
        },

        matrix_selector: '[name=matrix_collection]',
        matrix_quantity_cell_selector: '[name*=quantity]',

        _processButton: function() {
            var self = this;
            if (this.$button.data('enabled')) {
                if (this.options.displayType === 'dialog') {
                    this.$button.data('executor', function() {
                        TransitionHandler.call(self.$button);
                    });
                    this.$button.on('click', function(e) {
                        e.preventDefault();

                        self.showConfirmation(_.bind(function() {
                            $(this).data('executor').call();
                        }, this));
                    });
                } else {
                    this.$button.on('click', function(e) {
                        e.preventDefault();

                        self.showConfirmation(_.bind(function() {
                            mediator.execute('redirectTo', {url: self.$button.data('transition-url')}, {redirect: true});
                        }, self));
                    });
                }
            } else {
                this.$button.on('click', function(e) {
                    e.preventDefault();
                });
                if (this.$button.data('transition-condition-messages')) {
                    this.$button.popover({
                        'html': true,
                        'placement': 'bottom',
                        'container': $('body'),
                        'trigger': 'hover',
                        'title': '<i class="fa-exclamation-circle"></i>' + __('Unmet conditions'),
                        'content': this.$button.data('transition-condition-messages')
                    });
                }
            }
        },

        isEmptyMatrixExists: function ()
        {
            var self = this;
            var isEmptyMatrixExists = false;

            $(this.matrix_selector).each(function(){
                if (isEmptyMatrixExists) {
                    return;
                }

                var notZeroQuantityCells = $(this).find(self.matrix_quantity_cell_selector).filter(function() {
                    return parseInt($(this).val(), 10) > 0;
                });

                if (0 < $(notZeroQuantityCells).length) {
                    return;
                }

                isEmptyMatrixExists = true;
            });

            return isEmptyMatrixExists;
        },

        showConfirmation: function(callback) {
            var confirmModal = new StandardConfirmation(this.messages);

            if (false === this.isEmptyMatrixExists()) {
                callback();

                return;
            }

            confirmModal
                .off('ok')
                .on('ok')
                .open(callback);
        }
    });

    return ShoppingListMatrixCreateOrderButtonComponent;
});
