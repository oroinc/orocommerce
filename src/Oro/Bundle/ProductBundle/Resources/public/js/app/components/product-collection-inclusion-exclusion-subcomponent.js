define(function(require) {
    'use strict';

    var InclusionExclusionSubComponent;
    var BaseModel = require('oroui/js/app/models/base/model');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');

    InclusionExclusionSubComponent = BaseModel.extend({
        /**
         * @property {Object}
         */
        options: null,

        /**
         * @property {Object}
         */
        requiredOptions: [
            '$included',
            '$excluded',
            'sidebarComponentContainerId'
        ],

        initialize: function(options) {
            this.options = _.defaults({}, options, {
                delimiter: ','
            });
            this._checkOptions();

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
            var requiredMissed = this.requiredOptions.filter(_.bind(function(option) {
                return _.isUndefined(this.options[option]);
            }, this));
            if (requiredMissed.length) {
                throw new TypeError('Missing required option(s): ' + requiredMissed.join(', '));
            }
        },

        /**
         * @param {Array} ids
         */
        onAddToIncluded: function(ids) {
            this._removeFrom(this.options.$excluded, ids);
            this._addTo(this.options.$included, ids);
        },

        /**
         * @param {Array} ids
         */
        onAddToExcluded: function(ids) {
            this._removeFrom(this.options.$included, ids);
            this._addTo(this.options.$excluded, ids);
        },

        /**
         * @param {jQuery.Element} $to
         * @param {Array} ids
         * @private
         */
        _addTo: function($to, ids) {
            if (!Array.isArray(ids)) {
                return;
            }

            var currentState = $to.val().split(this.options.delimiter);

            $to.val(
                currentState.concat(ids).filter(function(value, index, array) {
                    return array.indexOf(value) === index && value !== '';
                }).join(this.options.delimiter)
            );
        },

        /**
         * @param {Array} ids
         */
        onRemoveFromIncluded: function(ids) {
            this._removeFrom(this.options.$included, ids);
        },

        /**
         * @param {Array} ids
         */
        onRemoveFromExcluded: function(ids) {
            this._removeFrom(this.options.$excluded, ids);
        },

        /**
         * @param {jQuery.Element} $from
         * @param {Array} ids
         * @private
         */
        _removeFrom: function($from, ids) {
            if (!Array.isArray(ids)) {
                return;
            }

            var currentState = $from.val().split(this.options.delimiter);

            $from.val(
                currentState.filter(function(value) {
                    return ids.indexOf(value) < 0 && value !== '';
                }).join(this.options.delimiter)
            );
        },

        dispose: function() {
            mediator.off(null, null, this);
        }
    });

    return InclusionExclusionSubComponent;
});
