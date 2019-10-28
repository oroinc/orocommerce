define(function(require) {
    'use strict';

    var ListItemProductPricesSubview;
    var BaseView = require('oroui/js/app/views/base/view');
    var mediator = require('oroui/js/mediator');
    var numeral = require('numeral');
    var localeSettings = require('orolocale/js/locale-settings');
    var PricesHelper = require('oropricing/js/app/prices-helper');
    var _ = require('underscore');

    ListItemProductPricesSubview = BaseView.extend({
        template: require('tpl-loader!oropricing/templates/product/list-item-product-prices.html'),

        modelAttr: {
            listedPrice: null
        },

        /**
         * @inheritDoc
         */
        constructor: function ListItemProductPricesSubview() {
            ListItemProductPricesSubview.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            ListItemProductPricesSubview.__super__.initialize.apply(this, arguments);

            this.showListedPrice = options.showListedPrice;
            this.showValuePrice = options.showValuePrice;
            this.changeUnitLabel = options.changeUnitLabel;

            _.each(this.modelAttr, function(attrValue, attrCode) {
                this.model.set(attrCode, attrValue);
            }, this);

            this.model.on('change:pricesByUnit', this.render, this);
            this.model.on('change:quantity', this.render, this);
            this.model.on('change:unit', this.render, this);
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.model) {
                this.model.off(null, null, this);
            }

            ListItemProductPricesSubview.__super__.dispose.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        getTemplateData: function() {
            return {
                listedPrice: this.findListedPrice(),
                price: this.findPrice(),
                localeSettings: localeSettings,
                numeral: numeral,
                showValuePrice: this.showValuePrice,
                showListedPrice: this.showListedPrice
            };
        },

        /**
         * Add the current price to unit label to improve UI on product view
         * Changes of 'unit_label' listen 'oroproduct/js/app/views/base-product-view'
         */
        setUnitLabel: function() {
            if (!this.changeUnitLabel) {
                return;
            }

            var price = this.findPrice();
            var unitLabel = null;
            if (price) {
                unitLabel = _.__('oro.pricing.price.formatted.unit', {
                    formattedUnit: _(price.formatted_unit).capitalize(),
                    formattedPrice: price.formatted_price
                });
            }

            this.model.set('unit_label', unitLabel);
        },

        /**
         * @inheritDoc
         */
        render: function() {
            ListItemProductPricesSubview.__super__.render.apply(this, arguments);
            this.setUnitLabel();

            mediator.trigger('layout:reposition');
            return this;
        },

        findPrice: function() {
            if (!this.showValuePrice) {
                return null;
            }

            var quantity = this.model.get('quantity');
            var unit = this.model.get('unit');
            var prices = this.model.get('pricesByUnit');

            return PricesHelper.findPrice(prices, unit, quantity);
        },

        findListedPrice: function() {
            if (!this.showListedPrice) {
                return null;
            }

            var prices = this.model.get('pricesByUnit');
            var listedPrice = this.model.get('listedPrice');
            if (null === listedPrice) {
                listedPrice = {};
                _.each(prices, function(unitData, unitKey) {
                    listedPrice[unitKey] = _.last(unitData);
                });
                this.model.set('listedPrice', listedPrice);
            }

            return listedPrice;
        }
    });

    return ListItemProductPricesSubview;
});
