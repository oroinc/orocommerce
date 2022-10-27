define(function(require) {
    'use strict';

    const BaseView = require('oroui/js/app/views/base/view');
    const mediator = require('oroui/js/mediator');
    const numeral = require('numeral');
    const localeSettings = require('orolocale/js/locale-settings');
    const PricesHelper = require('oropricing/js/app/prices-helper');
    const _ = require('underscore');

    const ListItemProductPricesSubview = BaseView.extend({
        template: require('tpl-loader!oropricing/templates/product/list-item-product-prices.html'),

        modelAttr: {
            listedPrice: null
        },

        /**
         * @inheritdoc
         */
        constructor: function ListItemProductPricesSubview(options) {
            ListItemProductPricesSubview.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            ListItemProductPricesSubview.__super__.initialize.call(this, options);

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
         * @inheritdoc
         */
        dispose: function() {
            if (this.model) {
                this.model.off(null, null, this);
            }

            ListItemProductPricesSubview.__super__.dispose.call(this);
        },

        /**
         * @inheritdoc
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

            const price = this.findPrice();
            let unitLabel = null;
            if (price) {
                unitLabel = _.__('oro.pricing.price.formatted.unit', {
                    formattedUnit: _(price.formatted_unit).capitalize(),
                    formattedPrice: price.formatted_price
                });
            }

            this.model.set('unit_label', unitLabel);
        },

        /**
         * @inheritdoc
         */
        render: function() {
            ListItemProductPricesSubview.__super__.render.call(this);
            this.setUnitLabel();

            mediator.trigger('layout:reposition');
            return this;
        },

        findPrice: function() {
            if (!this.showValuePrice) {
                return null;
            }

            const quantity = this.model.get('quantity');
            const unit = this.model.get('unit');
            const prices = this.model.get('pricesByUnit');

            return PricesHelper.findPrice(prices, unit, quantity);
        },

        findListedPrice: function() {
            if (!this.showListedPrice) {
                return null;
            }

            const prices = this.model.get('pricesByUnit');
            let listedPrice = this.model.get('listedPrice');
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
