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
            confirmCreateRedirectSelector: 'input[type=checkbox]',
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
         * @inheritDoc
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

            this._initEvents();
        },

        /**
         * @private
         */
        _initEvents: function() {
            this.$el.on('change', this.options.confirmCreateRedirectSelector, _.bind(function(event) {
                this.trigger('confirm-option-changed', $(event.target).prop('checked'));
            }, this));

            this.$el.on('shown', _.bind(function() {
                this.$el.find(this.options.confirmCreateRedirectSelector).prop('checked', this.options.confirmState);
            }, this));
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
