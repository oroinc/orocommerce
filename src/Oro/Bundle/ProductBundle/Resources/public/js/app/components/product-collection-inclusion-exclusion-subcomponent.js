define(function(require) {
    'use strict';

    var InclusionExclusionSubComponent;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var _ = require('underscore');
    var $ = require('jquery');
    var mediator = require('oroui/js/mediator');

    InclusionExclusionSubComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            sidebarComponentContainerId: null,
            delimiter: ',',
            selectors: {
                included: null,
                excluded: null
            }
        },

        /**
         * @property {Object}
         */
        requiredOptions: [
            'sidebarComponentContainerId'
        ],

        /**
         * @property {jQuery.Element}
         */
        $included: null,

        /**
         * @property {jQuery.Element}
         */
        $excluded: null,

        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, options || {});
            this._checkOptions();

            this.$included = this.options._sourceElement.find(this.options.selectors.included);
            this.$excluded = this.options._sourceElement.find(this.options.selectors.excluded);
            mediator.on(
                'product-collection-add-to-included:' + this.options.sidebarComponentContainerId,
                _.bind(this.onAddToIncluded, this)
            );
            mediator.on(
                'product-collection-add-to-excluded:' + this.options.sidebarComponentContainerId,
                _.bind(this.onAddToExcluded, this)
            );
            mediator.on(
                'product-collection-remove-from-included:' + this.options.sidebarComponentContainerId,
                _.bind(this.onRemoveFromIncluded, this)
            );
            mediator.on(
                'product-collection-remove-from-excluded:' +  this.options.sidebarComponentContainerId,
                _.bind(this.onRemoveFromExcluded, this)
            );
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

        /**
         * @param {Array} ids
         */
        onAddToIncluded: function(ids) {
            this._removeFrom(this.$excluded, ids);
            this._addTo(this.$included, ids);
        },

        /**
         * @param {Array} ids
         */
        onAddToExcluded: function(ids) {
            this._removeFrom(this.$included, ids);
            this._addTo(this.$excluded, ids);
        },

        /**
         * @param {jQuery.Element} $to
         * @param {Array} ids
         * @private
         */
        _addTo: function($to, ids) {
            if (!_.isArray(ids)) {
                return;
            }

            var currentState = $to.val().split(this.options.delimiter).concat(ids);
            currentState = _.filter(currentState, function(value, index, array) {
                return _.indexOf(array, value) === index && value !== '';
            });

            $to.val(currentState.join(this.options.delimiter)).trigger('change');
        },

        /**
         * @param {Array} ids
         */
        onRemoveFromIncluded: function(ids) {
            this._removeFrom(this.$included, ids);
        },

        /**
         * @param {Array} ids
         */
        onRemoveFromExcluded: function(ids) {
            this._removeFrom(this.$excluded, ids);
        },

        /**
         * @param {jQuery.Element} $from
         * @param {Array} ids
         * @private
         */
        _removeFrom: function($from, ids) {
            if (!_.isArray(ids)) {
                return;
            }

            var currentState = $from.val().split(this.options.delimiter);
            currentState = _.filter(currentState, function(value) {
                return _.indexOf(ids, value) < 0 && value !== '';
            });

            $from.val(currentState.join(this.options.delimiter)).trigger('change');
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            mediator.off(null, null, this);
        }
    });

    return InclusionExclusionSubComponent;
});
