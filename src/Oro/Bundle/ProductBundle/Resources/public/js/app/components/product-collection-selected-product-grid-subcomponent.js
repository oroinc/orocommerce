define(function(require) {
    'use strict';

    const BaseComponent = require('oroui/js/app/components/base/component');
    const _ = require('underscore');
    const $ = require('jquery');
    const mediator = require('oroui/js/mediator');
    const routing = require('routing');
    require('jquery-ui/effects/effect-highlight');

    const SelectedProductGridSubComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            applyQueryEventName: null,
            excludedControlsBlockAlias: null,
            includedControlsBlockAlias: null,
            excludedProductsGridName: null,
            includedProductsGridName: null,
            wait: 100,
            highlightColor: '#f1f7db',
            selectors: {
                excluded: null,
                included: null,
                tabFiltered: '[data-role="tab-filtered"]',
                tabExcluded: '[data-role="tab-excluded"]',
                tabIncluded: '[data-role="tab-included"]',
                counter: '[data-role="counter"]'
            },
            grids: [
                {
                    name: 'product-collection-grid',
                    type: 'filtered'
                },
                {
                    name: 'product-collection-content-variant-grid',
                    type: 'filtered'
                },
                {
                    name: 'product-collection-included-products-grid',
                    type: 'included'
                },
                {
                    name: 'product-collection-excluded-products-grid',
                    type: 'excluded'
                }
            ],
            counterRoute: 'oro_product_datagrid_count_get'
        },

        /**
         * @property {Object}
         */
        requiredOptions: [
            'applyQueryEventName',
            'excludedControlsBlockAlias',
            'includedControlsBlockAlias',
            'excludedProductsGridName',
            'includedProductsGridName'
        ],

        /**
         * @property {String}
         */
        namespace: null,

        /**
         * @property {Function}
         */
        reloadMainGrid: null,

        /**
         * @property {Object}
         */
        listen: {
            'grid_load:complete mediator': 'onGridLoadComplete'
        },

        /**
         * @inheritdoc
         */
        constructor: function SelectedProductGridSubComponent(options) {
            SelectedProductGridSubComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, options || {});
            this._checkOptions();

            this.$excluded = this.options._sourceElement.find(this.options.selectors.excluded);
            this.$included = this.options._sourceElement.find(this.options.selectors.included);

            this._initializeTabElements();

            mediator.on('grid-sidebar:load:' + this.options.excludedControlsBlockAlias, this.refreshExcludedGrid, this);
            mediator.on('grid-sidebar:load:' + this.options.includedControlsBlockAlias, this.refreshIncludedGrid, this);
            mediator.on(this.options.applyQueryEventName, this.onUpdateFilteredGrid, this);
            this.$excluded.on(
                'change' + this.eventNamespace(),
                _.throttle(this.onChangeExcluded.bind(this), this.options.wait)
            );
            this.$included.on(
                'change' + this.eventNamespace(),
                _.throttle(this.onChangeIncluded.bind(this), this.options.wait)
            );

            this.reloadMainGrid = _.debounce(this.triggerApplyQueryEvent.bind(this), this.options.wait);
        },

        _initializeTabElements: function() {
            this.tabs = {
                filtered: {
                    tab: this.options._sourceElement.find(this.options.selectors.tabFiltered),
                    loadCounter: _.debounce(this._loadCounter.bind(this), this.options.wait),
                    request: null,
                    update: 0
                },
                included: {
                    tab: this.options._sourceElement.find(this.options.selectors.tabIncluded),
                    loadCounter: _.debounce(this._loadCounter.bind(this), this.options.wait),
                    request: null,
                    update: 0
                },
                excluded: {
                    tab: this.options._sourceElement.find(this.options.selectors.tabExcluded),
                    loadCounter: _.debounce(this._loadCounter.bind(this), this.options.wait),
                    request: null,
                    update: 0
                }
            };

            this.tabs.filtered.counter = this.tabs.filtered.tab.find(this.options.selectors.counter);
            this.tabs.excluded.counter = this.tabs.excluded.tab.find(this.options.selectors.counter);
            this.tabs.included.counter = this.tabs.included.tab.find(this.options.selectors.counter);
        },

        /**
         * @param {Object} collection
         * @param {Object} gridElement
         */
        onGridLoadComplete: function(collection, gridElement) {
            const type = this._getGridType(collection.inputName);
            if (!_.isUndefined(type) && this.tabs[type].update > 0) {
                const foundGrid = this.options._sourceElement.find(gridElement);
                if (foundGrid.length) {
                    this.tabs[type].loadCounter(collection, type);
                }
            }
        },

        /**
         * @param {String} gridName
         * @returns {String|undefined}
         * @private
         */
        _getGridType: function(gridName) {
            for (let i = 0; i < this.options.grids.length; i++) {
                if (gridName.indexOf(this.options.grids[i].name) !== -1) {
                    return this.options.grids[i].type;
                }
            }

            return undefined;
        },

        /**
         * @param {Object} collection
         * @param {String} type
         * @private
         */
        _loadCounter: function(collection, type) {
            const tabData = this.tabs[type];
            const originalUrl = collection.url;
            const query = originalUrl.substring(originalUrl.indexOf('?'), originalUrl.length);
            const url = routing.generate(this.options.counterRoute, {gridName: collection.inputName});
            const tabType = `tab${type.charAt(0).toUpperCase() + type.slice(1)}`;
            const $tab = this.options._sourceElement.find(this.options.selectors[tabType]);

            if (tabData.request) {
                tabData.request.abort();
            }
            tabData.request = $.getJSON(
                url + query,
                count => {
                    $tab.find(this.options.selectors.counter).each((i, el) => {
                        $(el).html(count);
                    });
                }
            );
            tabData.request
                .done(() => {
                    $tab.effect('highlight', {color: this.options.highlightColor}, 1000);
                })
                .always(() => {
                    tabData.update--;
                    tabData.request = null;
                });
        },

        /**
         * @return {String}
         */
        eventNamespace: function() {
            if (this.namespace === null) {
                this.namespace = _.uniqueId('.selectedProductsOnChange');
            }

            return this.namespace;
        },

        /**
         * @private
         */
        _checkOptions: function() {
            const requiredMissed = _.filter(this.requiredOptions, option => {
                return _.isUndefined(this.options[option]);
            });
            if (requiredMissed.length) {
                throw new TypeError('Missing required option(s): ' + requiredMissed.join(', '));
            }

            const requiredSelectors = [];
            _.each(this.options.selectors, (selector, selectorName) => {
                if (!selector) {
                    requiredSelectors.push(selectorName);
                }
            });
            if (requiredSelectors.length) {
                throw new TypeError('Missing required selectors(s): ' + requiredSelectors.join(', '));
            }
        },

        onChangeExcluded: function() {
            this.refreshExcludedGrid(true);
            this.reloadMainGrid();
        },

        /**
         * @param {Boolean} reload
         */
        refreshExcludedGrid: function(reload) {
            if (reload === true) {
                this.tabs.excluded.update++;
            }
            this._refreshGrid(
                this.options.excludedControlsBlockAlias,
                this.options.excludedProductsGridName,
                this.$excluded.val(),
                reload
            );
        },

        onChangeIncluded: function() {
            this.refreshIncludedGrid(true);
            this.reloadMainGrid();
        },

        /**
         * @param {Boolean} reload
         */
        refreshIncludedGrid: function(reload) {
            if (reload === true) {
                this.tabs.included.update++;
            }
            this._refreshGrid(
                this.options.includedControlsBlockAlias,
                this.options.includedProductsGridName,
                this.$included.val(),
                reload
            );
        },

        triggerApplyQueryEvent: function() {
            mediator.trigger(this.options.applyQueryEventName);
        },

        onUpdateFilteredGrid: function() {
            this.tabs.filtered.update++;
        },

        /**
         * @param {String} controlsBlockAlias
         * @param {String} gridName
         * @param {String} value
         * @param {Boolean} reload
         * @private
         */
        _refreshGrid: function(controlsBlockAlias, gridName, value, reload) {
            const parameters = {
                updateUrl: false,
                reload: reload,
                params: {}
            };
            parameters.params[gridName] = {selectedProducts: value};

            mediator.trigger('grid-sidebar:change:' + controlsBlockAlias, parameters);
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            mediator.off(null, null, this);
            this.$excluded.off(this.eventNamespace());
            this.$included.off(this.eventNamespace());
        }
    });

    return SelectedProductGridSubComponent;
});
