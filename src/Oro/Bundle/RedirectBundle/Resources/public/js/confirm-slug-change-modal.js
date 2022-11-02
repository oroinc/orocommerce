define(function(require) {
    'use strict';

    const Modal = require('oroui/js/modal');
    const __ = require('orotranslation/js/translator');
    const _ = require('underscore');
    const $ = require('jquery');

    const ConfirmSlugChangeModal = Modal.extend({
        /**
         * @property {Object}
         */
        options: {
            messageTemplate: `
                <div><%- message %></div>
                <div><%= changedSlugs %></div>
                <div>
                    <br/>
                    <label class="checkbox-label" for="confirm-create-redirect">
                        <input type="checkbox" id="confirm-create-redirect" name="confirm-create-redirect">
                    <%- checkboxLabel %></label>
                </div>
            `,
            title: __('oro.redirect.confirm_slug_change.title'),
            okText: __('oro.redirect.confirm_slug_change.apply'),
            cancelText: __('oro.redirect.confirm_slug_change.cancel'),
            message: __('oro.redirect.confirm_slug_change.message'),
            checkboxLabel: __('oro.redirect.confirm_slug_change.checkbox_label'),
            confirmState: true
        },

        /**
         * @property {Object}
         */
        requiredOptions: ['changedSlugs'],

        /**
         * @property {Object}
         */
        events: {
            'shown.bs.modal': 'onShown',
            'change input[type=checkbox]': 'onChange'
        },

        /**
         * @inheritdoc
         */
        constructor: function ConfirmSlugChangeModal(options) {
            ConfirmSlugChangeModal.__super__.constructor.call(this, options);
        },

        /**
         * @param {Object} options
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            const requiredMissed = this.requiredOptions.filter(option => {
                return _.isUndefined(this.options[option]);
            });
            if (requiredMissed.length) {
                throw new TypeError('Missing required option(s): ' + requiredMissed.join(','));
            }

            options = _.extend({
                title: this.options.title,
                okText: this.options.okText,
                cancelText: this.options.cancelText,
                content: this._createContent(this.options.changedSlugs)
            }, options);
            ConfirmSlugChangeModal.__super__.initialize.apply(this, [options]);
        },

        /**
         * handler on modal shown
         */
        onShown: function() {
            this.$('input[type=checkbox]').prop('checked', this.options.confirmState);
        },

        /**
         * handler on change
         */
        onChange: function() {
            this.trigger('confirm-option-changed', $(event.target).prop('checked'));
        },

        /**
         * @param {String} changedSlugs
         * @returns {String}
         * @private
         */
        _createContent: function(changedSlugs) {
            const template = _.template(this.options.messageTemplate);

            return template({
                message: this.options.message,
                changedSlugs: changedSlugs,
                checkboxLabel: this.options.checkboxLabel
            });
        }
    });

    return ConfirmSlugChangeModal;
});
