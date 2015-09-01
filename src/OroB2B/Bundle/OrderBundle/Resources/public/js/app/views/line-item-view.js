define(function(require) {
    'use strict';

    var LineItemView;
    var $ = require('jquery');
    var _ = require('underscore');
    var layout = require('oroui/js/layout');
    var NumberFormatter = require('orolocale/js/formatter/number');
    var LineItemAbstractView = require('orob2border/js/app/views/line-item-abstract-view');

    /**
     * @export orob2border/js/app/views/line-item-view
     * @extends oroui.app.views.base.View
     * @class orob2border.app.views.LineItemView
     */
    LineItemView = LineItemAbstractView.extend({
        /**
         * @property {jQuery}
         */
        $priceOverridden: null,

        /**
         * @inheritDoc
         */
        initialize: function() {
            this.options = $.extend(true, {
                selectors: {
                    productType: '.order-line-item-type-product',
                    freeFormType: '.order-line-item-type-free-form'
                },
                bundledPriceTypeValue: '20'
            }, this.options);

            LineItemView.__super__.initialize.apply(this, arguments);

            this.initializeUnitLoader();
        },

        /**
         * Doing something after loading child components
         */
        handleLayoutInit: function() {
            this.$priceOverridden = this.$el.find(this.options.selectors.priceOverridden);
            layout.initPopover(this.$priceOverridden);

            LineItemView.__super__.handleLayoutInit.apply(this, arguments);

            this.subtotalFields([
                this.fieldsByName.product,
                this.fieldsByName.quantity,
                this.fieldsByName.productUnit,
                this.fieldsByName.priceValue,
                this.fieldsByName.priceType
            ]);

            this.initTypeSwitcher();
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

        onPriceValueChange: function() {
            this.fieldsByName.priceValue.removeClass('matched-price');

            this.renderPriceOverridden();
        },

        initTierPrices: function() {
            LineItemView.__super__.initTierPrices.apply(this, arguments);

            this.$tierPrices.on('click', 'a[data-price]', _.bind(function(e) {
                var $target = $(e.currentTarget);
                var priceType = this.fieldsByName.priceType.val();
                var priceValue = $target.data('price');
                var quantity = 1;

                if (priceType === this.options.bundledPriceTypeValue) {
                    quantity = parseFloat(this.fieldsByName.quantity.val());
                }

                this.fieldsByName.productUnit
                    .val($target.data('unit'))
                    .change();
                this.fieldsByName.priceValue
                    .val(priceValue * quantity)
                    .change();
            }, this));
        },

        initMatchedPrices: function() {
            LineItemView.__super__.initMatchedPrices.apply(this, arguments);

            if (_.isEmpty(this.fieldsByName.priceValue.val())) {
                this.fieldsByName.priceValue.addClass('matched-price');
            }
            this.fieldsByName.priceValue.change(_.bind(this.onPriceValueChange, this));

            this.$priceOverridden.on('click', 'a', _.bind(function() {
                this.fieldsByName.priceValue
                    .val(this.getMatchedPriceValue())
                    .change()
                    .addClass('matched-price');
            }, this));
        },

        /**
         * @inheritdoc
         */
        setMatchedPrices: function(matchedPrices) {
            LineItemView.__super__.setMatchedPrices.apply(this, arguments);

            if (this.fieldsByName.priceValue.hasClass('matched-price')) {
                this.fieldsByName.priceValue
                    .val(this.getMatchedPriceValue())
                    .change()
                    .addClass('matched-price');
            } else {
                this.renderPriceOverridden();
            }

            this.renderTierPrices();
        },

        renderPriceOverridden: function() {
            var priceValue = this.fieldsByName.priceValue.val();

            if (!_.isEmpty(this.matchedPrice) &&
                priceValue &&
                parseFloat(this.matchedPrice.value) !== parseFloat(priceValue)
            ) {
                this.$priceOverridden.show();
            } else {
                this.$priceOverridden.hide();
            }
        }
    });

    return LineItemView;
});
