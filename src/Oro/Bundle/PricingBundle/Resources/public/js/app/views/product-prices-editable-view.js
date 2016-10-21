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
            pricesHint: null,
            pricesHintContent: null,
            priceOverridden: null,
            priceValue: '[data-name="field__value"]'
        }),

        elementsEvents: _.extend({}, BaseProductPricesView.prototype.elementsEvents, {
            'priceValue onPriceSetManually': ['change', 'onPriceSetManually']
        }),

        modelElements: _.extend({}, BaseProductPricesView.prototype.modelElements, {
            price: 'priceValue'
        }),

        options: {
            precision: 4
        },

        templates: {
            pricesHint: '#product-prices-tier-button-template',
            pricesHintContent: '#product-prices-tier-table-template',
            priceOverridden: '#product-prices-price-overridden-template'
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, _.pick(options, _.keys(this.options)));
            this.templates = $.extend(true, {}, this.templates, options.templates || {});

            ProductPricesEditableView.__super__.initialize.apply(this, arguments);
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
        setPrice: function() {
            if (this.getElement('priceValue').hasClass('matched-price')) {
                this.setPriceValue(this.findPriceValue());
                this.getElement('priceValue').addClass('matched-price');
            } else {
                this.updateUI();
            }
        },

        setPriceValue: function(price) {
            this.model.set('price', this.calcTotalPrice(price));
        },

        updateUI: function() {
            this.renderPriceOverridden();
            this.renderHint();
        },

        initPriceOverridden: function() {
            var $priceOverridden = $(_.template(
                $(this.templates.priceOverridden).text()
            )());
            $priceOverridden = this.getElement('priceOverridden', $priceOverridden);

            layout.initPopover($priceOverridden);
            $priceOverridden.insertBefore(this.getElement('priceValue'))
                .on('click', 'a', _.bind(this.useFoundPrice, this));

            if (_.isEmpty(this.model.get('price'))) {
                this.getElement('priceValue').addClass('matched-price');
            }
        },

        initHint: function() {
            this.templates.pricesHintContent = _.template($(this.templates.pricesHintContent).text());

            var $pricesHint = $(_.template(
                $(this.templates.pricesHint).text()
            )());
            this.getElement('priceValue').after($pricesHint);

            var clickHandler = _.bind(this.setPriceFromHint, this);
            var $pricesHintButton = $pricesHint.find('i');
            this.getElement('pricesHint', $pricesHintButton)
                .on('shown', function() {
                    $pricesHintButton.data('popover').tip()
                        .find('a[data-price]')
                        .click(clickHandler);
                });
        },

        getHintContent: function() {
            if (_.isEmpty(this.prices)) {
                return '';
            }

            return $(this.templates.pricesHintContent({
                prices: this.prices,
                modelUnit: this.model.get('unit'),
                modelPrice: this.model.get('price'),
                clickable: true,
                formatter: NumberFormatter
            }));
        },

        onPriceSetManually: function() {
            this.getElement('priceValue').removeClass('matched-price');
        },

        setPriceFromHint: function(e) {
            var $target = $(e.currentTarget);
            this.model.set('unit', $target.data('unit'));
            this.setPriceValue($target.data('price'));
        },

        renderPriceOverridden: function() {
            var priceValue = this.model.get('price');
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
            var quantity = 1;
            return +(price * quantity).toFixed(this.options.precision);
        },

        useFoundPrice: function() {
            this.setPriceValue(this.findPriceValue());
            this.getElement('priceValue').addClass('matched-price');
        }
    });

    return ProductPricesEditableView;
});
