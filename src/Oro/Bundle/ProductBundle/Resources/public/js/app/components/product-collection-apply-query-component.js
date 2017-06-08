define(function(require) {
    'use strict';

    var ProductCollectionApplyQueryComponent;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var StandardConfirmation = require('oroui/js/standart-confirmation');
    var __ = require('orotranslation/js/translator');
    var _ = require('underscore');
    var $ = require('jquery');
    var mediator = require('oroui/js/mediator');
    var InclusionExclusionSubComponent =
        require('oroproduct/js/app/components/product-collection-inclusion-exclusion-subcomponent');
    var SelectedProductGridSubComponent =
        require('oroproduct/js/app/components/product-collection-selected-product-grid-subcomponent');

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
            controlsBlockAlias: null,
            gridName: null,
            sidebarComponentContainerId: null,
            excludedControlsBlockAlias: null,
            includedControlsBlockAlias: null,
            excludedProductsGridName: null,
            includedProductsGridName: null,
            selectors: {
                reset: null,
                apply: null,
                conditionBuilder: null,
                included: null,
                excluded: null
            }
        },

        /**
         * @property {Object}
         */
        requiredOptions: [
            'segmentDefinitionFieldName',
            'controlsBlockAlias',
            'gridName',
            'sidebarComponentContainerId',
            'excludedControlsBlockAlias',
            'includedControlsBlockAlias',
            'excludedProductsGridName',
            'includedProductsGridName'
        ],

        /**
         * @property string|null
         */
        initialDefinitionState: null,

        /**
         * @property string|null
         */
        initialIncluded: null,

        /**
         * @property string|null
         */
        initialExcluded: null,

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
         * @property {jQuery.Element}
         */
        $included: null,

        /**
         * @property {jQuery.Element}
         */
        $excluded: null,

        /**
         * @property {String}
         */
        namespace: null,

        /**
         * @property {Object}
         */
        inclusionExclusionSubComponent: null,

        /**
         * @property {Object}
         */
        selectedProductGridSubComponent: null,

        /**
         * @property {String}
         */
        applyQueryEventName: null,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, options || {});

            this._checkOptions();

            this.$conditionBuilder = this.options._sourceElement.find(this.options.selectors.conditionBuilder);
            this.$included = this.options._sourceElement.find(this.options.selectors.included);
            this.$excluded = this.options._sourceElement.find(this.options.selectors.excluded);
            this.$form = this.options._sourceElement.closest('form');

            this.options._sourceElement
                .on('click', this.options.selectors.apply, _.bind(this.onApplyQuery, this))
                .on('click', this.options.selectors.reset, _.bind(this.onReset, this))
                .on('query-designer:validate:not-blank-filters', _.bind(this.onFiltersValidate, this));

            this.initialDefinitionState = this._getSegmentDefinition();
            this.initialIncluded = this.$included.val();
            this.initialExcluded = this.$excluded.val();

            if (this.initialDefinitionState !== null && this.initialDefinitionState !== '') {
                this.currentDefinitionState = this.initialDefinitionState;
                mediator.on('grid-sidebar:load:' + this.options.controlsBlockAlias, this._applyQuery, this);
            }
            this.$form.on('submit' + this.eventNamespace(), _.bind(this.onSubmit, this));

            this.applyQueryEventName = 'productCollection:applyQuery:' + this.eventNamespace();
            mediator.on(this.applyQueryEventName, _.bind(this.applyQuery, this));
            this._initializeInclusionExclusionSubComponent();
            this._initializeSelectedProductGridsSubComponent();

            mediator.on('condition-builder:options:prepare', this.onConditionBuilderOptionsPrepare, this);
            this._enableHiddenFieldValidation();
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
            if (this._isConditionBuilderValid()) {
                mediator.trigger(this.applyQueryEventName);
            }
        },

        applyQuery: function() {
            this.currentDefinitionState = this._getSegmentDefinition();
            this._applyQuery(true);
        },

        onConfirmModalOk: function() {
            this._setConfirmed();
            this.$form.trigger('submit');
        },

        onReset: function(e) {
            var filters = this.initialDefinitionState ? JSON.parse(this.initialDefinitionState).filters : [];
            this.$conditionBuilder.conditionBuilder('setValue', filters);
            this.currentDefinitionState = this.initialDefinitionState;
            this.$included.val(this.initialIncluded).trigger('change');
            this.$excluded.val(this.initialExcluded).trigger('change');
            this.onApplyQuery(e);
        },

        /**
         * @param {jQuery.Event} e
         * @param {Object} data
         */
        onFiltersValidate: function(e, data) {
            if (this.$included.val()) {
                data.result = true;
            }
        },

        /**
         * @param {Object} options
         */
        onConditionBuilderOptionsPrepare: function(options) {
            options.validation = {
                'condition-item': {
                    NotBlank: {message: 'oro.product.product_collection.blank_condition_item'}
                },
                'conditions-group': {
                    NotBlank: {message: 'oro.product.product_collection.blank_condition_group'}
                }
            };
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
        _applyQuery: function(reload) {
            var parameters = {
                ignoreVisibility: true,
                updateUrl: false,
                reload: reload,
                params: {}
            };

            parameters.params['sd_' + this.options.gridName] = this.currentDefinitionState;
            parameters.params['sd_' + this.options.gridName + ':incl'] = this.$included.val();
            parameters.params['sd_' + this.options.gridName + ':excl'] = this.$excluded.val();

            mediator.trigger('grid-sidebar:change:' + this.options.controlsBlockAlias, parameters);
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
                            content: __('oro.product.product_collection.filter_query.confirmation_modal_content'),
                            okText: __('oro.product.product_collection.filter_query.continue')
                        })
                    );
                }
                this.$form.data('productCollectionApplyQueryModal').on('ok', _.bind(this.onConfirmModalOk, this));
                this.confirmModalInitialized = true;
            }

            this.$form.data('productCollectionApplyQueryModal').open();
        },

        /**
         * @private
         */
        _initializeInclusionExclusionSubComponent: function() {
            var options = {
                _sourceElement: this.options._sourceElement,
                sidebarComponentContainerId: this.options.sidebarComponentContainerId,
                selectors: {
                    included: this.options.selectors.included,
                    excluded: this.options.selectors.excluded
                }
            };
            this.inclusionExclusionSubComponent = new InclusionExclusionSubComponent(options);
        },

        /**
         * @private
         */
        _initializeSelectedProductGridsSubComponent: function() {
            var options = {
                _sourceElement: this.options._sourceElement,
                applyQueryEventName: this.applyQueryEventName,
                excludedControlsBlockAlias: this.options.excludedControlsBlockAlias,
                includedControlsBlockAlias: this.options.includedControlsBlockAlias,
                excludedProductsGridName: this.options.excludedProductsGridName,
                includedProductsGridName: this.options.includedProductsGridName,
                selectors: {
                    included: this.options.selectors.included,
                    excluded: this.options.selectors.excluded
                }
            };
            this.selectedProductGridSubComponent = new SelectedProductGridSubComponent(options);
        },

        /**
         * @return {Boolean}
         * @private
         */
        _isConditionBuilderValid: function() {
            var $form = this.$conditionBuilder.closest('form');
            if (!$form.data('validator')) {
                return true;
            }

            $form.valid();

            var invalidElements = $form.validate().invalidElements();
            if (!invalidElements.length) {
                return true;
            }

            var conditionBuilderInvalidElements = _.filter(invalidElements, _.bind(function(value) {
                return $(value).parents(this.options.selectors.conditionBuilder).length;
            }, this));

            return !conditionBuilderInvalidElements.length;
        },

        /**
         * If conditionBuilder located in oro-tabs, change form's setting in order to validate hidden fields too.
         * Because of it can be hidden.
         *
         * @private
         */
        _enableHiddenFieldValidation: function() {
            var $form = this.$conditionBuilder.closest('form');
            if ($form.data('validator') && this.$conditionBuilder.parents('.oro-tabs')) {
                $form.validate().settings.ignore = '';
            }
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            if (this.$form.data('productCollectionApplyQueryModal')) {
                this.$form.data('productCollectionApplyQueryModal').off('ok', _.bind(this.onConfirmModalOk, this));
            }
            this.$form.off(this.eventNamespace());
            mediator.off('grid-sidebar:load:' + this.options.controlsBlockAlias);
            mediator.off(this.applyQueryEventName);

            ProductCollectionApplyQueryComponent.__super__.dispose.call(this);
        }
    });

    return ProductCollectionApplyQueryComponent;
});
