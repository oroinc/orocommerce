define(function(require) {
    'use strict';

    var ProductCollectionApplyQueryComponent;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var StandardConfirmation = require('oroui/js/standart-confirmation');
    var SingletonService = require('oroui/js/singleton-service');
    var __ = require('orotranslation/js/translator');
    var _ = require('underscore');
    var $ = require('jquery');
    var mediator = require('oroui/js/mediator');

    /**
     * Perform synchronization between segment definition filters block and grid. By click on "apply the query" button
     * will apply the definition filters to the related grid.
     */
    ProductCollectionApplyQueryComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            segmentDefinitionSelectorTemplate: 'input[name="%s"]'
        },

        /**
         * @property {Object}
         */
        requiredOptions: [
            'variantFormId',
            'conditionBuilderId',
            'segmentDefinitionFieldName',
            'gridWidgetContainerSelector',
            'controlsBlockAlias'
        ],

        /**
         * @property string|null
         */
        initialDefinitionState: null,

        /**
         * @property string|null
         */
        currentDefinitionState: null,

        /**
         * @property {Boolean}
         */
        confirmed: false,

        /**
         * @property {Object}
         */
        confirmModal: null,

        /**
         * @property {jQuery.Element}
         */
        $applyBtn: null,

        /**
         * @property {jQuery.Element}
         */
        $resetBtn: null,

        /**
         * @property {jQuery.Element}
         */
        $applyQueryFilter: null,

        /**
         * @property {jQuery.Element}
         */
        $conditionBuilder: null,

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
            this.$conditionBuilder = $('#' + this.options.variantFormId).find('#' + this.options.conditionBuilderId);
            this.$applyBtn = this.options._sourceElement.find('.filter-apply');
            this.$resetBtn = this.options._sourceElement.find('.filter-reset');

            this.$applyBtn.on('click', _.bind(this.onApplyQuery, this));
            this.$resetBtn.on('click', _.bind(this.onReset, this));

            this.initialDefinitionState = this.currentDefinitionState = this._getSegmentDefinition();
            this._getForm().on('submit', $.proxy(this.onSubmit, this));
        },

        /**
         * @param {jQuery.Event} event
         * @return {Boolean}
         */
        onSubmit: function(event) {
            if (!$(event.target).valid()) {
                return true;
            }

            if (!this._isCurrentDefinitionStateChange()) {
                return true;
            }

            if (this._isConfirmed()) {
                return true;
            }

            event.stopImmediatePropagation();
            this._showConfirmModal();

            return false;
        },

        /**
         * @param {jQuery.Event} e
         */
        onApplyQuery: function(e) {
            e.preventDefault();
            $(this.options.gridWidgetContainerSelector).removeClass('hide');
            var segmentDefinition = this._getSegmentDefinition();
            mediator.trigger('grid-sidebar:change:' + this.options.controlsBlockAlias, {
                params: {segmentDefinition: segmentDefinition}
            });
            this.currentDefinitionState = segmentDefinition;
        },

        onConfirmModalOk: function() {
            this._setConfirmed();
            this._getForm().trigger('submit');
        },

        onReset: function() {
            var filters = this.initialDefinitionState ? JSON.parse(this.initialDefinitionState).filters : [];
            this.$conditionBuilder.conditionBuilder('setValue', filters);
            this.currentDefinitionState = this.initialDefinitionState;
        },

        /**
         * @return {jQuery.Element}
         * @private
         */
        _getForm: function() {
            if (!this.$form) {
                this.$form = this.options._sourceElement.closest('form');
            }

            return this.$form;
        },

        /**
         * @return string
         * @private
         */
        _getSegmentDefinition: function() {
            return $(
                this.options.segmentDefinitionSelectorTemplate.replace('%s', this.options.segmentDefinitionFieldName)
            ).val();
        },

        /**
         * @return boolean
         * @private
         */
        _isCurrentDefinitionStateChange: function() {
            return this.currentDefinitionState !== this._getSegmentDefinition();
        },

        /**
         * @private
         */
        _setConfirmed: function() {
            this._getForm().data('apply-query-confirmed', true);
        },

        /**
         * @return boolean
         * @private
         */
        _isConfirmed: function() {
            return this._getForm().data('apply-query-confirmed');
        },

        /**
         * @private
         */
        _showConfirmModal: function() {
            if (!this.confirmModal) {
                var modalOptions = {
                    content: __('oro.product.product_collection.filter_query.confirmation_modal_content')
                };
                this.confirmModal = SingletonService.getInstance(
                    'productCollectionApplyQueryModal',
                    StandardConfirmation,
                    modalOptions
                )
                    .on('ok', $.proxy(this.onConfirmModalOk, this));
            }
            this.confirmModal.open();
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.$applyBtn.off();
            this.$resetBtn.off();
            if (this.confirmModal) {
                this.confirmModal.off('ok');
                delete this.confirmModal;
            }

            this._getForm().off('submit', $.proxy(this.onSubmit, this));

            ProductCollectionApplyQueryComponent.__super__.dispose.call(this);
        }
    });

    return ProductCollectionApplyQueryComponent;
});
