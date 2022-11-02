define(function(require) {
    'use strict';

    const BaseComponent = require('oroui/js/app/components/base/component');
    const _ = require('underscore');
    const $ = require('jquery');
    const mediator = require('oroui/js/mediator');

    const InclusionExclusionSubComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            scope: null,
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
            'scope'
        ],

        /**
         * @property {jQuery.Element}
         */
        $included: null,

        /**
         * @property {jQuery.Element}
         */
        $excluded: null,

        /**
         * @inheritdoc
         */
        constructor: function InclusionExclusionSubComponent(options) {
            InclusionExclusionSubComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, options || {});
            this._checkOptions();

            this.$included = this.options._sourceElement.find(this.options.selectors.included);
            this.$excluded = this.options._sourceElement.find(this.options.selectors.excluded);
            mediator.on(
                'product-collection-add-to-included:' + this.options.scope,
                this.onAddToIncluded.bind(this)
            );
            mediator.on(
                'product-collection-add-to-excluded:' + this.options.scope,
                this.onAddToExcluded.bind(this)
            );
            mediator.on(
                'product-collection-remove-from-included:' + this.options.scope,
                this.onRemoveFromIncluded.bind(this)
            );
            mediator.on(
                'product-collection-remove-from-excluded:' + this.options.scope,
                this.onRemoveFromExcluded.bind(this)
            );
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

            let currentState = $to.val().split(this.options.delimiter).concat(ids);
            currentState = _.filter(currentState, function(value, index, array) {
                return value !== '';
            });

            const newVal = _.uniq(currentState.sort(), true).join(this.options.delimiter);
            if ($to.val() !== newVal) {
                $to.val(newVal).trigger('change');
            }
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

            ids = _.map(ids, function(value) {
                return parseInt(value);
            });

            let currentState = $from.val().split(this.options.delimiter);
            currentState = _.filter(currentState, function(value) {
                return value !== '' && _.indexOf(ids, parseInt(value)) < 0;
            });

            const newVal = _.uniq(currentState.sort(), true).join(this.options.delimiter);
            if ($from.val() !== newVal) {
                $from.val(newVal).trigger('change');
            }
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
