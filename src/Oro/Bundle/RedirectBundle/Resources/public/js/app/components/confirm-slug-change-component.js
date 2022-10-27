define(function(require) {
    'use strict';

    const BaseComponent = require('oroui/js/app/components/base/component');
    const ConfirmSlugChangeModal = require('ororedirect/js/confirm-slug-change-modal');
    const mediator = require('oroui/js/mediator');
    const messenger = require('oroui/js/messenger');
    const __ = require('orotranslation/js/translator');
    const _ = require('underscore');
    const $ = require('jquery');

    const ConfirmSlugChangeComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            textFieldSelector: '[type=text]',
            localizationFallbackItemSelector: '.fallback-item',
            localizationFallbackItemLabelSelector: '.fallback-item-label'
        },

        /**
         * @property {Object}
         */
        requiredOptions: ['slugFields', 'createRedirectCheckbox', 'disabled'],

        /**
         * @property {Object}
         */
        $slugFields: null,

        /**
         * @property {Array}
         */
        slugFieldsInitialState: [],

        /**
         * @property {Object}
         */
        $createRedirectCheckbox: null,

        /**
         * @property {Boolean}
         */
        disabled: false,

        /**
         * @property {Boolean}
         */
        confirmed: false,

        /**
         * @property {Object}
         */
        confirmModal: null,

        /**
         * @inheritdoc
         */
        constructor: function ConfirmSlugChangeComponent(options) {
            ConfirmSlugChangeComponent.__super__.constructor.call(this, options);
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

            this.initializeElements();
            mediator.on('page:afterChange', this.initializeElements, this);
        },

        initializeElements: function() {
            this.disabled = this.options.disabled;
            this.onSubmit = this.onSubmit.bind(this);

            if (!this.disabled) {
                this.$form = this.options._sourceElement.closest('form');
                this.$slugFields = this.$form.find(this.options.slugFields).filter(this.options.textFieldSelector);
                this.$createRedirectCheckbox = this.$form.find(this.options.createRedirectCheckbox);
                this._saveSlugFieldsInitialState();
                this.$form
                    .off(this.eventNamespace())
                    .on('submit' + this.eventNamespace(), this.onSubmit);
            }
        },

        eventNamespace: function() {
            return '.delegateEvents' + this.cid;
        },

        /**
         * @param {jQuery.Event} event
         * @return {Boolean}
         */
        onSubmit: function(event) {
            const validator = $(event.target).data('validator');

            if (validator && !validator.valid()) {
                return true;
            }

            if (!this._isSlugFieldsChanged()) {
                return true;
            }

            if (this.confirmed) {
                return true;
            }

            event.stopImmediatePropagation();
            this._removeConfirmModal();

            this.loadSlugListAndShowConfirmModal();

            return false;
        },

        loadSlugListAndShowConfirmModal: function() {
            const formData = this.$form.serialize();
            let urls = {};

            mediator.execute('showLoading');
            $.ajax({
                url: this.options.changedSlugsUrl,
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: response => {
                    mediator.execute('hideLoading');
                    urls = response;

                    if (!_.isArray(urls)) {
                        this.confirmModal = new ConfirmSlugChangeModal({
                            changedSlugs: this._getUrlsList(urls),
                            confirmState: this.$createRedirectCheckbox.prop('checked')
                        })
                            .on('ok', this.onConfirmModalOk.bind(this))
                            .on('confirm-option-changed', this.onConfirmModalOptionChange.bind(this))
                            .open();
                    } else {
                        this.onConfirmModalOk();
                    }
                },
                error: function() {
                    mediator.execute('hideLoading');
                    messenger.notificationFlashMessage('error', __('oro.ui.unexpected_error'));
                }
            });
        },

        onConfirmModalOk: function() {
            this.confirmed = true;
            this.$form.trigger('submit');
        },

        /**
         * @param {Boolean} confirmState
         */
        onConfirmModalOptionChange: function(confirmState) {
            this.$createRedirectCheckbox.prop('checked', confirmState);
        },

        /**
         * @inheritdoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            if (this.disabled) {
                return;
            }

            this._removeConfirmModal();

            if (this.$form) {
                this.$form.off('submit', this.onSubmit);
            }
            mediator.off(null, null, this);

            ConfirmSlugChangeComponent.__super__.dispose.call(this);
        },

        /**
         * @private
         */
        _saveSlugFieldsInitialState: function() {
            this.$slugFields.each((index, item) => {
                this.slugFieldsInitialState.splice(index, 0, $(item).val());
            });
        },

        /**
         * @returns {Boolean}
         * @private
         */
        _isSlugFieldsChanged: function() {
            let isChanged = false;
            this.$slugFields.each((index, item) => {
                if (this.slugFieldsInitialState[index] !== $(item).val()) {
                    isChanged = true;
                    return false;
                }
                return true;
            });
            return isChanged;
        },

        /**
         /**
         * @param {Object} urls
         * @returns {String}
         * @private
         */
        _getUrlsList: function(urls) {
            let list = '';
            for (const localization in urls) {
                if (urls.hasOwnProperty(localization)) {
                    const oldSlug = _.macros('oroui::renderDirection')({
                        content: urls[localization].before
                    }).trim();
                    const newSlug = _.macros('oroui::renderDirection')({
                        content: urls[localization].after
                    }).trim();
                    list += '\n' + __(
                        'oro.redirect.confirm_slug_change.changed_localized_slug_item',
                        {
                            old_slug: oldSlug,
                            new_slug: newSlug,
                            purpose: localization
                        }
                    );
                }
            }

            return list;
        },

        /**
         * @returns {String}
         * @private
         */
        _getSlugPurpose: function($item) {
            return $item.closest(this.options.localizationFallbackItemSelector)
                .find(this.options.localizationFallbackItemLabelSelector).text();
        },

        /**
         * @private
         */
        _removeConfirmModal: function() {
            if (this.confirmModal) {
                this.confirmModal.off();
                this.confirmModal.dispose();
                delete this.$createRedirectCheckbox;
                delete this.$slugFields;
                delete this.$form;
                delete this.confirmModal;
            }
        }
    });

    return ConfirmSlugChangeComponent;
});
