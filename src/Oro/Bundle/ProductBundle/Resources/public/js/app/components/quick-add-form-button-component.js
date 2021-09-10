define(function(require) {
    'use strict';

    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const $ = require('jquery');
    const BaseComponent = require('oroui/js/app/components/base/component');
    const Modal = require('oroui/js/modal');

    const QuickAddFormButtonComponent = BaseComponent.extend({
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
         * @inheritdoc
         */
        constructor: function QuickAddFormButtonComponent(options) {
            QuickAddFormButtonComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
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

            this.$button.on('click', this.submit.bind(this));
        },

        /**
         * @inheritdoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            delete this.messages;

            QuickAddFormButtonComponent.__super__.dispose.call(this);
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

            _.each(this.options, (selector, data) => {
                if (data === '_sourceElement') {
                    return;
                }

                this.$form.find(selector).val(this.$button.data(data));
            });

            if (this.confirmation) {
                this.getConfirmDialog(this.executeConfiguredAction.bind(this)).open();
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
            const confirmModal = new this.confirmModalConstructor({
                title: __(this.messages.confirm_title),
                content: this.confirmMessage,
                okText: __(this.messages.confirm_ok),
                cancelText: __(this.messages.confirm_cancel)
            });

            confirmModal.on('ok', callback);

            return confirmModal;
        },

        executeConfiguredAction: function() {
            this.$form.submit();
        }
    });

    return QuickAddFormButtonComponent;
});
