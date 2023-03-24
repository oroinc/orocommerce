define(function(require) {
    'use strict';

    const BaseComponent = require('oroui/js/app/components/base/component');
    const StandardConfirmation = require('oroui/js/standart-confirmation');
    const __ = require('orotranslation/js/translator');
    const _ = require('underscore');
    const $ = require('jquery');
    const mediator = require('oroui/js/mediator');
    const InclusionExclusionSubComponent =
        require('oroproduct/js/app/components/product-collection-inclusion-exclusion-subcomponent');
    const SelectedProductGridSubComponent =
        require('oroproduct/js/app/components/product-collection-selected-product-grid-subcomponent');

    /**
     * Perform synchronization between segment definition filters block and grid. By click on "apply the query" button
     * will apply the definition filters to the related grid.
     */
    const ProductCollectionApplyQueryComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            segmentId: null,
            segmentDefinitionSelectorTemplate: 'input[name="%s"]',
            controlsBlockAlias: null,
            gridName: null,
            scope: null,
            excludedControlsBlockAlias: null,
            includedControlsBlockAlias: null,
            excludedProductsGridName: null,
            includedProductsGridName: null,
            selectors: {
                reset: null,
                apply: null,
                included: null,
                excluded: null
            }
        },

        /**
         * @property {Object}
         */
        requiredOptions: [
            'segmentDefinitionFieldName',
            'segmentId',
            'controlsBlockAlias',
            'gridName',
            'scope',
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
         * @inheritdoc
         */
        constructor: function ProductCollectionApplyQueryComponent(options) {
            ProductCollectionApplyQueryComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, options || {});

            this._checkOptions();

            const {selectors: elems} = this.options;
            this.$included = this.options._sourceElement.find(elems.included);
            this.$excluded = this.options._sourceElement.find(elems.excluded);
            this.$form = this.options._sourceElement.closest('form');

            const eventNS = this.eventNamespace();
            this.options._sourceElement
                .on(`click${eventNS}`, elems.apply, this.onApplyQuery.bind(this))
                .on(`click${eventNS}`, elems.reset, this.onReset.bind(this))
                .on(`patchInitialState${eventNS}`, elems.included, () => this.initialIncluded = this.$included.val())
                .on(`patchInitialState${eventNS}`, elems.excluded, () => this.initialExcluded = this.$excluded.val())
                .on(`query-designer:validate:not-empty-filters${eventNS}`, this.onFiltersValidate.bind(this));

            this.initialDefinitionState = this._getSegmentDefinition();
            this.initialIncluded = this.$included.val();
            this.initialExcluded = this.$excluded.val();

            if (this.initialDefinitionState !== null && this.initialDefinitionState !== '') {
                this.currentDefinitionState = this.initialDefinitionState;
                mediator.on('grid-sidebar:load:' + this.options.controlsBlockAlias, this._applyQuery, this);
            }
            this.$form.on(`submit${eventNS}`, this.onSubmit.bind(this));

            this.applyQueryEventName = `productCollection:applyQuery:${eventNS}`;
            mediator.on(this.applyQueryEventName, this.applyQuery.bind(this));
            this._initializeInclusionExclusionSubComponent();
            this._initializeSelectedProductGridsSubComponent();

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
            const filters = this.initialDefinitionState ? JSON.parse(this.initialDefinitionState).filters : [];
            this.updateSegmentDefinitionValue('filters', filters);
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
            const filters = this.fetchSegmentDefinitionValue('filters');
            if (!_.isEmpty(filters) || this.$included.val()) {
                data.result = true;
            }
        },

        _checkOptions: function() {
            const requiredMissed = this.requiredOptions.filter(option => {
                return _.isUndefined(this.options[option]);
            });
            if (requiredMissed.length) {
                throw new TypeError('Missing required option(s): ' + requiredMissed.join(', '));
            }

            const requiredSelectors = [];
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
            const parameters = {
                updateUrl: false,
                reload: reload,
                params: {}
            };

            parameters.params['si_' + this.options.gridName] = this.options.segmentId;
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
            return this._getSegmentDefinitionInput().val();
        },

        _getSegmentDefinitionInput: function() {
            const name = this.options.segmentDefinitionFieldName;
            return $(this.options.segmentDefinitionSelectorTemplate.replace('%s', name));
        },

        /**
         * Loads data from the segment definition input
         *
         * @param {string=} key name of data branch
         */
        fetchSegmentDefinitionValue: function(key) {
            let data = {};
            const json = this._getSegmentDefinitionInput().val();
            if (json) {
                try {
                    data = JSON.parse(json);
                } catch (e) {
                    return undefined;
                }
            }
            return key ? data[key] : data;
        },

        /**
         * Saves data to the segment definition input
         *
         * @param {Object|string} value data if single argument is passed or key name of data branch
         * @param {Object=} value data for data branch
         */
        updateSegmentDefinitionValue: function(value, ...rest) {
            let key;
            let data = this.fetchSegmentDefinitionValue();
            if (rest.length === 1) {
                key = value;
                value = rest[0];
                data[key] = value;
            } else {
                data = value;
            }
            this._getSegmentDefinitionInput().val(JSON.stringify(data)).trigger('change');
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
            const confirmModal = new StandardConfirmation({
                content: __('oro.product.product_collection.filter_query.confirmation_modal_content'),
                okText: __('oro.product.product_collection.filter_query.continue')
            });

            confirmModal.on('ok', this.onConfirmModalOk.bind(this));
            confirmModal.open();
        },

        /**
         * @private
         */
        _initializeInclusionExclusionSubComponent: function() {
            const options = {
                _sourceElement: this.options._sourceElement,
                scope: this.options.scope,
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
            const options = {
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
            const $form = this.$form;
            if (!$form.data('validator')) {
                return true;
            }

            $form.valid();

            const invalidElements = $form.validate().invalidElements();
            if (!invalidElements.length) {
                return true;
            }

            const $conditionBuilder = this.options._sourceElement.find('.condition-builder');
            const conditionBuilderInvalidElements = _.filter(invalidElements, value => {
                return $conditionBuilder[0] && $.contains($conditionBuilder[0], value);
            });

            return !conditionBuilderInvalidElements.length;
        },

        /**
         * If conditionBuilderView located in oro-tabs, change form's setting in order to validate hidden fields too.
         * Because of it can be hidden.
         *
         * @private
         */
        _enableHiddenFieldValidation: function() {
            const $form = this.$form;
            if ($form.data('validator')) {
                $form.validate()
                    .settings
                    .ignore = ':hidden:not([type=hidden]):not(:parent.' + this.options.controlsBlockAlias + ')';
            }
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.options._sourceElement.off(this.eventNamespace());
            this.$form.off(this.eventNamespace());
            mediator.off('grid-sidebar:load:' + this.options.controlsBlockAlias);
            mediator.off(this.applyQueryEventName);

            ProductCollectionApplyQueryComponent.__super__.dispose.call(this);
        }
    });

    return ProductCollectionApplyQueryComponent;
});
