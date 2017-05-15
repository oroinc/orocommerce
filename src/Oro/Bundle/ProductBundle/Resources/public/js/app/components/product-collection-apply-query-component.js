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
            controlsBlockAlias: null,
            gridName: null,
            selectors: {
                reset: null,
                apply: null,
                gridWidgetContainer: null,
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
            'gridName'
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
                .on('click', this.options.selectors.reset, _.bind(this.onReset, this));

            this.initialDefinitionState = this._getSegmentDefinition();
            this.initialIncluded = this.$included.val();
            this.initialExcluded = this.$excluded.val();

            if (this.initialDefinitionState !== null && this.initialDefinitionState !== '') {
                this.currentDefinitionState = this.initialDefinitionState;
                mediator.on('grid-sidebar:load:' + this.options.controlsBlockAlias, this._applyQuery, this);
            }
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
            this.currentDefinitionState = this._getSegmentDefinition();
            e.preventDefault();
            $(this.options.selectors.gridWidgetContainer).removeClass('hide');
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
            this.$included.val(this.initialIncluded);
            this.$excluded.val(this.initialExcluded);
            this.onApplyQuery(e);
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

        dispose: function() {
            if (this.disposed) {
                return;
            }

            if (this.$form.data('productCollectionApplyQueryModal')) {
                this.$form.data('productCollectionApplyQueryModal').off('ok', _.bind(this.onConfirmModalOk, this));
            }
            this.$form.off(this.eventNamespace());
            mediator.off('grid-sidebar:load:' + this.options.controlsBlockAlias);

            ProductCollectionApplyQueryComponent.__super__.dispose.call(this);
        }
    });

    return ProductCollectionApplyQueryComponent;
});
