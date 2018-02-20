define(function(require) {
    'use strict';

    var QuickAddFormButtonComponent;
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var $ = require('jquery');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var Modal = require('oroui/js/modal');

    QuickAddFormButtonComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {},

        /**
         * @property {jQuery}
         */
        $button: null,

        /**
         * @property {jQuery}
         */
        $form: null,

        /**
         * @property {Object}
         */
        messages: null,

        /**
         * @property {Object}
         */
        defaultMessages: {
            confirm_title: 'oro.product.dialog.quick_add.confirm.title',
            confirm_content: 'oro.product.dialog.quick_add.confirm.content',
            confirm_ok: 'oro.product.dialog.quick_add.confirm.continue',
            confirm_cancel: 'oro.product.dialog.quick_add.confirm.cancel'
        },

        /**
         * @property {Boolean}
         */
        confirmation: false,

        /**
         * @property {Function}
         */
        confirmModalConstructor: Modal,

        /** @property {String} */
        confirmMessage: null,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            this.$button = this.options._sourceElement;
            this.$form = this.$button.closest('form');
            this.confirmMessage = __(this.defaultMessages.confirm_content);

            if (options.confirmation) {
                if (options.shopping_list_limit) {
                    this.confirmMessage = __(
                        this.defaultMessages.confirm_content,
                        {count: options.shopping_list_limit},
                        options.shopping_list_limit
                    );
                }
                this.confirmation = $.extend(true, {}, this.confirmation, options.confirmation);
            }

            // make own messages property from prototype
            this.messages = _.extend({}, this.defaultMessages, this.messages);

            this.$button.on('click', _.bind(this.submit, this));
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            delete this.messages;
            delete this.confirmModal;

            QuickAddFormButtonComponent.__super__.dispose.apply(this, arguments);
        },

        /**
         * @param {$.Event} e
         */
        submit: function(e) {
            e.preventDefault();
            e.stopPropagation();

            if (this.formHasErrors()) {
                return;
            }

            _.each(this.options, _.bind(function(selector, data) {
                if (data === '_sourceElement') {
                    return;
                }

                this.$form.find(selector).val(this.$button.data(data));
            }, this));

            if (this.confirmation) {
                this.getConfirmDialog(_.bind(this.executeConfiguredAction, this)).open();
            } else {
                this.executeConfiguredAction();
            }
        },

        formHasErrors: function() {
            return !this.$form.validate().valid() ||
                this.$form.find('.product-autocomplete-error .validation-failed:visible').length;
        },

        /**
         * Get view for confirm modal
         *
         * @return {Function}
         */
        getConfirmDialog: function(callback) {
            if (!this.confirmModal) {
                this.confirmModal = (new this.confirmModalConstructor({
                    title: __(this.messages.confirm_title),
                    content: this.confirmMessage,
                    okText: __(this.messages.confirm_ok),
                    cancelText: __(this.messages.confirm_cancel)
                }));
                this.listenTo(this.confirmModal, 'ok', callback);
            }

            return this.confirmModal;
        },

        executeConfiguredAction: function() {
            this.$form.submit();
        }
    });

    return QuickAddFormButtonComponent;
});
