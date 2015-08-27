define(function(require) {
    'use strict';

    var LineItemAbstractView;
    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var layout = require('oroui/js/layout');
    var NumberFormatter = require('orolocale/js/formatter/number');
    var SubtotalsListener = require('orob2border/js/app/listener/subtotals-listener');
    var BaseView = require('oroui/js/app/views/base/view');

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
            ftid: '',
            selectors: {
                tierPrices: '.order-line-item-tier-prices',
                priceOverridden: '.order-line-item-price-overridden',
                tierPricesTemplate: '#order-line-item-tier-prices-template'
            }
        },

        /**
         * @property {jQuery}
         */
        $fields: null,

        /**
         * @property {jQuery}
         */
        $tierPrices: null,

        /**
         * @property {jQuery}
         */
        $priceOverridden: null,

        /**
         * @property {Object}
         */
        fieldsByName: null,

        /**
         * @property {Object}
         */
        tierPricesTemplate: null,

        /**
         * @property {Object}
         */
        tierPrices: null,

        /**
         * @property {Object}
         */
        matchedPrice: null,

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

            this.$tierPrices = this.$el.find(this.options.selectors.tierPrices);
            this.$priceOverridden = this.$el.find(this.options.selectors.priceOverridden);

            this.initMatchedPrices();

            this.fieldsByName.priceValue.change(_.bind(this.onPriceValueChange, this));
        },

        onPriceValueChange: function() {
            this.fieldsByName.priceValue.removeClass('matched-price');

            this.renderPriceOverridden();
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

        /**
         * @param {jQuery|Array} $fields
         */
        subtotalFields: function($fields) {
            SubtotalsListener.listen($fields);
        },

        initTierPrices: function() {
            this.tierPricesTemplate = _.template($(this.options.selectors.tierPricesTemplate).text());

            this.fieldsByName.product.change(_.bind(function(e) {
                var productId = e.currentTarget.value;
                if (productId.length === 0) {
                    this.setTierPrices({});
                } else {
                    mediator.trigger('order:load:products-tier-prices', [productId], _.bind(this.setTierPrices, this));
                }
            }, this));

            mediator.trigger('order:get:products-tier-prices', _.bind(this.setTierPrices, this));

            if (this.fieldsByName.priceValue) {
                this.$tierPrices.on('click', 'a[data-price]', _.bind(function(e) {
                    this.fieldsByName.priceValue
                        .val($(e.currentTarget).data('price'))
                        .change();
                }, this));

                this.$priceOverridden.on('click', 'a', _.bind(function(e) {
                    this.fieldsByName.priceValue
                        .val(this.matchedPrice.value)
                        .change()
                        .addClass('matched-price');
                }, this));
            }
        },

        /**
         * @param {Object} tierPrices
         */
        setTierPrices: function(tierPrices) {
            this.tierPrices = tierPrices[this.fieldsByName.product.val()] || {};
            this.renderTierPrices();
        },

        renderTierPrices: function() {
            var $button = this.$tierPrices.find('i');
            $button.data('popover', null);

            var content = '';
            if (!_.isEmpty(this.tierPrices)) {
                content = this.tierPricesTemplate({
                    tierPrices: this.tierPrices,
                    formatter: NumberFormatter
                });
                $button.removeClass('disabled');
            } else {
                $button.addClass('disabled');
            }

            $button.data('content', content);
            layout.initPopover(this.$tierPrices);
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
                this.matchedPrice = matchedPrices[identifier] || {};

                if (this.fieldsByName.priceValue.hasClass('matched-price')) {
                    this.fieldsByName.priceValue
                        .val(this.matchedPrice.value)
                        .change()
                        .addClass('matched-price');
                } else {
                    this.renderPriceOverridden();
                }
            }
        },

        renderPriceOverridden: function() {
            var priceValue = this.fieldsByName.priceValue.val();

            if (this.matchedPrice && priceValue && parseFloat(this.matchedPrice.value) !== parseFloat(priceValue)) {
                this.$priceOverridden.show();
            } else {
                this.$priceOverridden.hide();
            }

            layout.initPopover(this.$priceOverridden);
        },

        /**
         * @returns {string}
         */
        _getMatchedPriceIdentifier: function() {
            var productId = this.fieldsByName.product.val();
            var unitCode = this.fieldsByName.productUnit.val();
            var quantity = this.fieldsByName.quantity.val();

            return productId.length === 0 ? null : productId + '-' + unitCode + '-' + quantity;
        }
    });

    return LineItemAbstractView;
});
