define(function(require) {
    'use strict';

    var ConfirmSlugChangeComponent;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var ConfirmSlugChangeModal = require('ororedirect/js/confirm-slug-change-modal');
    var mediator = require('oroui/js/mediator');
    var __ = require('orotranslation/js/translator');
    var _ = require('underscore');
    var $ = require('jquery');

    ConfirmSlugChangeComponent = BaseComponent.extend({
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
        requiredOptions: ['slugFields', 'createRedirectCheckbox'],

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
        confirmed: false,

        /**
         * @property {Object}
         */
        confirmModal: null,

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

            mediator.on('page:afterChange', this.initializeElements, this);
        },

        initializeElements: function() {
            this.$form = this.options._sourceElement.closest('form');
            this.$slugFields = this.$form.find(this.options.slugFields).filter(this.options.textFieldSelector);
            this.$createRedirectCheckbox = this.$form.find(this.options.createRedirectCheckbox);
            this._saveSlugFieldsInitialState();
            this.$form.on('submit', _.bind(this.onSubmit, this));
        },

        /**
         * @param {jQuery.Event} event
         * @return {Boolean}
         */
        onSubmit: function(event) {
            if (!$(event.target).valid()) {
                return true;
            }

            if (!this._isSlugFieldsChanged()) {
                return true;
            }

            if (this.confirmed) {
                return true;
            }

            this._removeConfirmModal();
            this.confirmModal = new ConfirmSlugChangeModal({
                'changedSlugs': this._getChangedSlugsList(),
                'confirmState': this.$createRedirectCheckbox.prop('checked')
            })
                .on('ok', _.bind(this.onConfirmModalOk, this))
                .on('confirm-option-changed', _.bind(this.onConfirmModalOptionChange, this))
                .open();

            return false;
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
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            this._removeConfirmModal();

            this.$form.off('submit', _.bind(this.onSubmit, this));
            mediator.off(null, null, this);

            ConfirmSlugChangeComponent.__super__.dispose.call(this);
        },

        /**
         * @private
         */
        _saveSlugFieldsInitialState: function() {
            this.$slugFields.each(_.bind(function(index, item) {
                this.slugFieldsInitialState.splice(index, 0, $(item).val());
            }, this));
        },

        /**
         * @returns {Boolean}
         * @private
         */
        _isSlugFieldsChanged: function() {
            var isChanged = false;
            this.$slugFields.each(_.bind(function(index, item) {
                if(this.slugFieldsInitialState[index] !== $(item).val()) {
                    isChanged = true;
                    return false;
                }
                return true;
            }, this));
            return isChanged;
        },

        /**
         * @returns {String}
         * @private
         */
        _getChangedSlugsList: function() {
            var list = '';
            this.$slugFields.each(_.bind(function(index, item) {
                if (this.slugFieldsInitialState[index] !== $(item).val()) {
                    var purpose = this._getSlugPurpose($(item));
                    if (purpose) {
                        list += '\n' + __(
                                'oro.redirect.confirm_slug_change.changed_localized_slug_item',
                                {
                                    'old_slug': this.slugFieldsInitialState[index],
                                    'new_slug': $(item).val(),
                                    'purpose': purpose
                                }
                            );
                    } else {
                        list += '\n' + __(
                                'oro.redirect.confirm_slug_change.changed_slug_item',
                                {
                                    'old_slug': this.slugFieldsInitialState[index],
                                    'new_slug': $(item).val()
                                }
                            );
                    }
                }
            }, this));
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
                delete this.confirmModal;
            }
        }
    });

    return ConfirmSlugChangeComponent;
});
