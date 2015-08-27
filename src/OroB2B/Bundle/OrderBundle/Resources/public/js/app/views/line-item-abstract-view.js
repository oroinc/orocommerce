define(function(require) {
    'use strict';

    var LineItemAbstractView;
    var $ = require('jquery');
    var _ = require('underscore');
    var SubtotalsListener = require('orob2border/js/app/listener/subtotals-listener');
    var BaseView = require('oroui/js/app/views/base/view');
    var mediator = require('oroui/js/mediator');

    /**
     * @export orob2border/js/app/views/line-item-abstract-view
     * @extends oroui.app.views.base.View
     * @class orob2border.app.views.LineItemAbstractView
     */
    LineItemAbstractView = BaseView.extend({
        /**
         * @property {Object}
         */
        options: {
            ftid: ''
        },

        /**
         * @property {jQuery}
         */
        $fields: null,

        /**
         * @property {Object}
         */
        fieldsByName: null,

        /**
         * @property {Object}
         */
        matchedPrices: null,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, options || {});
            if (!this.options.ftid) {
                this.options.ftid = this.$el.data('content').toString()
                    .replace(/[^a-zA-Z0-9]+/g, '_').replace(/_+$/, '');
            }

            this.initLayout().done(_.bind(this.handleLayoutInit, this));
        },

        /**
         * Doing something after loading child components
         */
        handleLayoutInit: function() {
            var self = this;

            this.$fields = this.$el.find(':input[data-ftid]');
            this.fieldsByName = {};
            this.$fields.each(function() {
                var $field = $(this);
                var name = self.normalizeName($field.data('ftid').replace(self.options.ftid + '_', ''));
                self.fieldsByName[name] = $field;
            });

            this.initMatchedPrices();
        },

        /**
         * Convert name with "_" to name with upper case, example: some_name > someName
         *
         * @param {String} name
         *
         * @returns {String}
         */
        normalizeName: function(name) {
            name = name.split('_');
            for (var i = 1, iMax = name.length; i < iMax; i++) {
                name[i] = name[i][0].toUpperCase() + name[i].substr(1);
            }
            return name.join('');
        },

        initMatchedPrices: function() {
            var fields = [
                this.fieldsByName.product,
                this.fieldsByName.productUnit,
                this.fieldsByName.quantity
            ];

            var self = this;
            _.each(fields, function(field) {
                field.change(_.bind(self.updateMatchedPrices, self));
            });

            mediator.trigger('order:get:line-items-matched-prices', _.bind(this.setMatchedPrices, this));
        },

        /**
         * Trigger subtotals update
         */
        updateMatchedPrices: function() {
            var productId = this.fieldsByName.product.val();
            var unitCode = this.fieldsByName.productUnit.val();
            var quantity = this.fieldsByName.quantity.val();

            if (productId.length === 0) {
                this.setMatchedPrices({});
            } else {
                mediator.trigger(
                    'order:load:line-items-matched-prices',
                    [{'product': productId, 'unit': unitCode, 'qty': quantity}],
                    _.bind(this.setMatchedPrices, this)
                );
            }
        },

        /**
         * @param {Object} matchedPrices
         */
        setMatchedPrices: function(matchedPrices) {
            var identifier = this._getMatchedPriceIdentifier();
            if (identifier) {
                this.matchedPrices = matchedPrices[identifier] || {};
            }
        },

        /**
         * @returns {string}
         */
        _getMatchedPriceIdentifier: function() {
            var productId = this.fieldsByName.product.val();
            var unitCode = this.fieldsByName.productUnit.val();
            var quantity = this.fieldsByName.quantity.val();

            return productId.length === 0 ? null : productId + '-' + unitCode + '-' + quantity;
        },

        /**
         * @param {jQuery|Array} $fields
         */
        subtotalFields: function($fields) {
            SubtotalsListener.listen($fields);
        }
    });

    return LineItemAbstractView;
});
