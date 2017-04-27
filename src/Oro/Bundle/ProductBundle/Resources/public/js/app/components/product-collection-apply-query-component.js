define(function(require) {
    'use strict';

    var ProductCollectionApplyQueryComponent;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var StandardConfirmation = require('oroui/js/standart-confirmation');
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
            segmentDefinitionSelectorTemplate: 'input[name="%s"]',
            selectors: {
                reset: null,
                apply: null,
                gridWidgetContainer: null,
                conditionBuilder: null
            }
        },

        /**
         * @property {Object}
         */
        requiredOptions: [
            'segmentDefinitionFieldName',
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
         * @property {Boolean}
         */
        confirmModalInitialized: false,

        /**
         * @property {jQuery.Element}
         */
        $conditionBuilder: null,

        /**
         * @property {jQuery.Element}
         */
        $form: null,

        /**
         * @property {String}
         */
        namespace: null,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, options || {});

            this._checkOptions();

            this.$conditionBuilder = this.options._sourceElement.find(this.options.selectors.conditionBuilder);
            this.$form = this.options._sourceElement.closest('form');

            this.options._sourceElement
                .on('click', this.options.selectors.apply, _.bind(this.onApplyQuery, this))
                .on('click', this.options.selectors.reset, _.bind(this.onReset, this));

            this.initialDefinitionState = this.currentDefinitionState = this._getSegmentDefinition();
            this.$form.on('submit' + this.eventNamespace(), _.bind(this.onSubmit, this));
        },

        /**
         * @return {String}
         */
        eventNamespace: function() {
            if (this.namespace === null) {
                this.namespace = _.uniqueId('.applyQuery');
            }

            return this.namespace;
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
            this._applyQuery();
        },

        onConfirmModalOk: function() {
            this._setConfirmed();
            this.$form.trigger('submit');
        },

        onReset: function() {
            var filters = this.initialDefinitionState ? JSON.parse(this.initialDefinitionState).filters : [];
            this.$conditionBuilder.conditionBuilder('setValue', filters);
            this.currentDefinitionState = this.initialDefinitionState;
            this._applyQuery();
        },

        _checkOptions: function() {
            var requiredMissed = this.requiredOptions.filter(_.bind(function(option) {
                return _.isUndefined(this.options[option]);
            }, this));
            if (requiredMissed.length) {
                throw new TypeError('Missing required option(s): ' + requiredMissed.join(', '));
            }

            var requiredSelectors = [];
            _.each(this.options.selectors, function(selector, selectorName) {
                if (!selector) {
                    requiredSelectors.push(selectorName);
                }
            });
            if (requiredSelectors.length) {
                throw new TypeError('Missing required selectors(s): ' + requiredSelectors.join(', '));
            }
        },

        /**
         * @private
         */
        _applyQuery: function() {
            $(this.options.selectors.gridWidgetContainer).removeClass('hide');
            var segmentDefinition = this._getSegmentDefinition();
            mediator.trigger('grid-sidebar:change:' + this.options.controlsBlockAlias, {
                params: {segmentDefinition: segmentDefinition}
            });
            this.currentDefinitionState = segmentDefinition;
        },

        /**
         * @return {String}
         * @private
         */
        _getSegmentDefinition: function() {
            return $(
                this.options.segmentDefinitionSelectorTemplate.replace('%s', this.options.segmentDefinitionFieldName)
            ).val();
        },

        /**
         * @return {Boolean}
         * @private
         */
        _isCurrentDefinitionStateChange: function() {
            return this.currentDefinitionState !== this._getSegmentDefinition();
        },

        /**
         * @private
         */
        _setConfirmed: function() {
            this.$form.data('apply-query-confirmed', true);
        },

        /**
         * @return {Boolean}
         * @private
         */
        _isConfirmed: function() {
            return this.$form.data('apply-query-confirmed');
        },

        /**
         * @private
         */
        _showConfirmModal: function() {
            if (!this.confirmModalInitialized) {
                if (!this.$form.data('productCollectionApplyQueryModal')) {
                    this.$form.data(
                        'productCollectionApplyQueryModal',
                        new StandardConfirmation({
                            content: __('oro.product.product_collection.filter_query.confirmation_modal_content')
                        })
                    );
                }
                this.$form.data('productCollectionApplyQueryModal').on('ok', _.bind(this.onConfirmModalOk, this));
                this.confirmModalInitialized = true;
            }

            this.$form.data('productCollectionApplyQueryModal').open();
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            if (this.$form.data('productCollectionApplyQueryModal')) {
                this.$form.data('productCollectionApplyQueryModal').off('ok', _.bind(this.onConfirmModalOk, this));
            }
            this.$form.off(this.eventNamespace());

            ProductCollectionApplyQueryComponent.__super__.dispose.call(this);
        }
    });

    return ProductCollectionApplyQueryComponent;
});
