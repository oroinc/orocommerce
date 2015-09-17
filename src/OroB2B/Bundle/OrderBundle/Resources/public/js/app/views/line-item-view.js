define(function(require) {
    'use strict';

    var LineItemView;
    var $ = require('jquery');
    var _ = require('underscore');
    var layout = require('oroui/js/layout');
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
            var $product = this.$el.find('div' + this.options.selectors.productType);
            var $freeForm = this.$el.find('div' + this.options.selectors.freeFormType);

            var showFreeFormType = function() {
                $product.hide();
                $freeForm.show();
            };

            var showProductType = function() {
                $freeForm.hide();
                $product.show();
            };

            $freeForm.find('a').click(_.bind(function() {
                showProductType();
                $freeForm.find(':input').val('').change();
            }, this));

            $product.find('a').click(_.bind(function() {
                showFreeFormType();
                this.fieldsByName.product.select2('val', '').change();
            }, this));

            if (this.fieldsByName.freeFormProduct.val() !== '') {
                showFreeFormType();
            } else {
                showProductType();
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

        resetData: function() {
            LineItemView.__super__.resetData.apply(this, arguments);

            if (this.fieldsByName.hasOwnProperty('priceValue')) {
                this.fieldsByName.priceValue.val(null);
            }
        },

        initMatchedPrices: function() {
            LineItemView.__super__.initMatchedPrices.apply(this, arguments);

            if (_.isEmpty(this.fieldsByName.priceValue.val())) {
                this.fieldsByName.priceValue.addClass('matched-price');
            }
            this.addFieldEvents('priceValue', this.onPriceValueChange);

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
        updateMatchedPrices: function() {
            this.fieldsByName.priceValue.trigger('value:changing');
            LineItemView.__super__.updateMatchedPrices.apply(this, arguments);
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
            this.fieldsByName.priceValue.trigger('value:changed');
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
