define(function(require) {
    'use strict';

    var ProductPricesEditableView;
    var BaseProductPricesView = require('oropricing/js/app/views/base-product-prices-view');
    var NumberFormatter = require('orolocale/js/formatter/number');
    var layout = require('oroui/js/layout');
    var $ = require('jquery');
    var _ = require('underscore');

    ProductPricesEditableView = BaseProductPricesView.extend({
        elements: _.extend({}, BaseProductPricesView.prototype.elements, {
            pricesHint: ['$html', '#product-prices-tier-button-template'],
            pricesHintContent: ['$html', '#product-prices-tier-table-template'],
            priceOverridden: null,
            priceValue: '[data-name="field__value"]'
        }),

        modelAttr: {
            found_price: null
        },

        elementsEvents: _.extend({}, BaseProductPricesView.prototype.elementsEvents, {
            'priceValue onPriceSetManually': ['change', 'onPriceSetManually']
        }),

        modelElements: _.extend({}, BaseProductPricesView.prototype.modelElements, {
            price: 'priceValue'
        }),

        options: {
            matchedPriceEnabled: true,
            precision: 4
        },

        templates: {
            priceOverridden: '#product-prices-price-overridden-template'
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, _.pick(options, _.keys(this.options)));
            this.templates = $.extend(true, {}, this.templates, options.templates || {});

            ProductPricesEditableView.__super__.initialize.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        deferredInitialize: function(options) {
            ProductPricesEditableView.__super__.deferredInitialize.apply(this, arguments);

            this.initPriceOverridden();
            this.initHint();
        },

        /**
         * @inheritDoc
         */
        dispose: function(options) {
            delete this.templates;
            ProductPricesEditableView.__super__.dispose.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        findPrice: function() {
            var price = ProductPricesEditableView.__super__.findPrice.apply(this, arguments);
            this.model.set('found_price', price);
            this.getElement('priceValue').data('found_price', price);
            return price;
        },

        /**
         * @inheritDoc
         */
        setFoundPrice: function() {
            this.findPrice();
            if (this.options.matchedPriceEnabled && this.getElement('priceValue').hasClass('matched-price')) {
                this.setPriceValue(this.findPriceValue());
            }

            this.updateUI();
        },

        setPriceValue: function(price) {
            this.model.set('price', this.calcTotalPrice(price));
        },

        updateUI: function() {
            this.renderPriceOverridden();
            this.renderHint();
        },

        initPriceOverridden: function() {
            if (!this.options.matchedPriceEnabled) {
                return;
            }
            var $priceOverridden = $(_.template(
                $(this.templates.priceOverridden).text()
            )());
            $priceOverridden = this.getElement('priceOverridden', $priceOverridden);

            layout.initPopover($priceOverridden);
            $priceOverridden.insertBefore(this.getElement('priceValue'))
                .on('click', 'a', _.bind(this.useFoundPrice, this));

            if (_.isEmpty(this.getElement('priceValue').val()) && this.options.matchedPriceEnabled) {
                this.getElement('priceValue').addClass('matched-price');
            }
        },

        initHint: function() {
            this.templates.pricesHintContent = _.template(this.getElement('pricesHintContent').text());

            var $pricesHint = $(_.template(this.getElement('pricesHint').text())());
            this.$elements.pricesHint = $pricesHint;
            this.getElement('priceValue').after($pricesHint);

            var clickHandler = _.bind(this.setPriceFromHint, this);
            $pricesHint
                .on('shown', function() {
                    $pricesHint.data('popover').tip()
                        .find('a[data-price]')
                        .click(clickHandler);
                });
        },

        getHintContent: function() {
            if (_.isEmpty(this.prices)) {
                return '';
            }

            return $(this.templates.pricesHintContent({
                model: this.model.attributes,
                prices: this.prices,
                matchedPrice: this.findPrice(),
                clickable: true,
                formatter: NumberFormatter
            }));
        },

        onPriceSetManually: function(e, options) {
            if (options.manually && this.options.matchedPriceEnabled) {
                this.getElement('priceValue').removeClass('matched-price');
            }
        },

        setPriceFromHint: function(e) {
            this.getElement('priceValue').removeClass('matched-price');
            var $target = $(e.currentTarget);
            this.model.set('unit', $target.data('unit'));
            this.setPriceValue($target.data('price'));
        },

        renderPriceOverridden: function() {
            if (!this.options.matchedPriceEnabled) {
                return;
            }
            var priceValue = this.getElement('priceValue').val();
            var price = this.findPriceValue();

            if (price !== null &&
                this.calcTotalPrice(price) !== parseFloat(priceValue)
            ) {
                this.getElement('priceOverridden').show();
            } else {
                this.getElement('priceOverridden').hide();
            }
        },

        calcTotalPrice: function(price) {
            if (price === null) {
                return price;
            }
            var quantity = 1;
            return +(price * quantity).toFixed(this.options.precision);
        },

        useFoundPrice: function() {
            if (!this.options.matchedPriceEnabled) {
                return;
            }
            this.setPriceValue(this.findPriceValue());
            this.getElement('priceValue').addClass('matched-price');
        }
    });

    return ProductPricesEditableView;
});
