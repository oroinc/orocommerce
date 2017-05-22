define(function(require) {
    'use strict';

    var SelectedProductGridSubComponent;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var _ = require('underscore');
    var $ = require('jquery');
    var mediator = require('oroui/js/mediator');

    SelectedProductGridSubComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            applyQueryEventName: null,
            excludedControlsBlockAlias: null,
            includedControlsBlockAlias: null,
            excludedProductsGridName: null,
            includedProductsGridName: null,
            delimiter: ',',
            wait: 100,
            selectors: {
                excluded: null,
                included: null
            }
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

        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, options || {});
            this._checkOptions();

            this.$excluded = this.options._sourceElement.find(this.options.selectors.excluded);
            this.$included = this.options._sourceElement.find(this.options.selectors.included);

            mediator.on('grid-sidebar:load:' + this.options.excludedControlsBlockAlias, this.refreshExcludedGrid, this);
            mediator.on('grid-sidebar:load:' + this.options.includedControlsBlockAlias, this.refreshIncludedGrid, this);
            this.$excluded.on(
                'change' + this.eventNamespace(),
                _.throttle(_.bind(this.onChangeExcluded, this), this.options.wait)
            );
            this.$included.on(
                'change' + this.eventNamespace(),
                _.throttle(_.bind(this.onChangeIncluded, this), this.options.wait)
            );

            this.reloadMainGrid = _.debounce(_.bind(this.triggerApplyQueryEvent, this), this.options.wait);
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
            var requiredMissed = _.filter(this.requiredOptions, _.bind(function(option) {
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

        onChangeExcluded: function() {
            this.refreshExcludedGrid(true);
            this.reloadMainGrid();
        },

        /**
         * @param {Boolean} reload
         */
        refreshExcludedGrid: function(reload) {
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

        /**
         * @param {String} controlsBlockAlias
         * @param {String} gridName
         * @param {String} value
         * @param {Boolean} reload
         * @private
         */
        _refreshGrid: function(controlsBlockAlias, gridName, value, reload) {
            var parameters = {
                ignoreVisibility: true,
                updateUrl: false,
                reload: reload,
                params: {}
            };
            parameters.params[gridName] = {selectedProducts: value.split(this.options.delimiter)};

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
