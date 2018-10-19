define(function(require) {
    'use strict';

    var ConfirmSlugChangeModal;
    var Modal = require('oroui/js/modal');
    var __ = require('orotranslation/js/translator');
    var _ = require('underscore');
    var $ = require('jquery');

    ConfirmSlugChangeModal = Modal.extend({
        /**
         * @property {Object}
         */
        options: {
            messageTemplate: '<%- message %><%- changedSlugs %><br><br>' +
            '<label class="checkbox" for="confirm-create-redirect">' +
            '<input type="checkbox" id="confirm-create-redirect" name="confirm-create-redirect">' +
            '<%- checkboxLabel %></label>',
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
         * @inheritDoc
         */
        constructor: function ConfirmSlugChangeModal() {
            ConfirmSlugChangeModal.__super__.constructor.apply(this, arguments);
        },

        /**
         * @param {Object} options
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            var requiredMissed = this.requiredOptions.filter(_.bind(function(option) {
                return _.isUndefined(this.options[option]);
            }, this));
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
            var template = _.template(this.options.messageTemplate);
            var content = template({
                message: this.options.message,
                changedSlugs: changedSlugs,
                checkboxLabel: this.options.checkboxLabel
            });
            return _.nl2br(content);
        }
    });

    return ConfirmSlugChangeModal;
});
