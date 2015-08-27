define(function(require) {
    'use strict';

    var LineItemView;
    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var NumberFormatter = require('orolocale/js/formatter/number');
    var LineItemAbstractView = require('orob2border/js/app/views/line-item-abstract-view');

    /**
     * @export orob2border/js/app/views/line-item-view
     * @extends oroui.app.views.base.View
     * @class orob2border.app.views.LineItemView
     */
    LineItemView = LineItemAbstractView.extend({
        /**
         * @property {Object}
         */
        options: {
            selectors: {
                productType: '.order-line-item-type-product',
                freeFormType: '.order-line-item-type-free-form',
                tierPrices: '.order-line-item-tier-prices',
                tierPricesTemplate: '#order-line-item-tier-prices-template'
            }
        },

        /**
         * @property {jQuery}
         */
        $tierPrices: null,

        /**
         * @property {Object}
         */
        tierPricesTemplate: null,

        /**
         * @property {Object}
         */
        tierPrices: null,

        /**
         * Doing something after loading child components
         */
        handleLayoutInit: function() {
            LineItemView.__super__.handleLayoutInit.apply(this, arguments);

            this.tierPricesTemplate = _.template($(this.options.selectors.tierPricesTemplate).text());
            this.$tierPrices = this.$el.find(this.options.selectors.tierPrices);

            this.subtotalFields([
                this.fieldsByName.product,
                this.fieldsByName.quantity,
                this.fieldsByName.productUnit,
                this.fieldsByName.priceValue,
                this.fieldsByName.priceType
            ]);

            this.initTypeSwitcher();

            this.initTierPrices();
        },

        initTypeSwitcher: function() {
            var $freeFormType = this.$el.find('a' + this.options.selectors.freeFormType).click(_.bind(function() {
                this.fieldsByName.product.select2('val', '').change();
                this.$el.find('div' + this.options.selectors.productType).hide();
                this.$el.find('div' + this.options.selectors.freeFormType).show();
            }, this));

            var $productType = this.$el.find('a' + this.options.selectors.productType).click(_.bind(function() {
                var $freeFormTypeContainers = this.$el.find('div' + this.options.selectors.freeFormType);
                $freeFormTypeContainers.find(':input').val('').change();
                $freeFormTypeContainers.hide();
                this.$el.find('div' + this.options.selectors.productType).show();
            }, this));

            if (this.fieldsByName.freeFormProduct.val() !== '') {
                $freeFormType.click();
            } else {
                $productType.click();
            }
        },

        initTierPrices: function() {
            this.fieldsByName.product.change(_.bind(function(e) {
                var productId = e.currentTarget.value;
                if (productId.length === 0) {
                    this.setTierPrices({});
                } else {
                    mediator.trigger('order:load:products-tier-prices', [productId], _.bind(this.setTierPrices, this));
                }
            }, this));

            mediator.trigger('order:get:products-tier-prices', _.bind(this.setTierPrices, this));
        },

        /**
         * @param {Object} tierPrices
         */
        setTierPrices: function(tierPrices) {
            this.tierPrices = tierPrices[this.fieldsByName.product.val()] || {};
            this.renderTierPrices();
        },

        renderTierPrices: function() {
            this.$tierPrices.html(this.tierPricesTemplate({
                tierPrices: this.tierPrices,
                formatter: NumberFormatter
            }));

            if (_.isEmpty(this.tierPrices)) {
                return;
            }

            var $tierPricesTable = this.$tierPrices.find('table:first');
            this.$tierPrices.find('i').click(function() {
                $tierPricesTable.toggle();
            });
            this.$tierPrices.find('a[data-price]').click(_.bind(function(e) {
                this.fieldsByName.priceValue.val($(e.currentTarget).data('price'));
            }, this));
        }
    });

    return LineItemView;
});
