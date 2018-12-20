define(function(require) {
    'use strict';

    var ProductPricesEditableView;
    var pricesHint = require('tpl!oropricing/templates/product/prices-tier-button.html');
    var pricesHintContent = require('tpl!oropricing/templates/product/prices-tier-table.html');
    var priceOverridden = require('tpl!oropricing/templates/product/prices-price-overridden.html');
    var BaseProductPricesView = require('oropricing/js/app/views/base-product-prices-view');
    var NumberFormatter = require('orolocale/js/formatter/number');
    var Popover = require('bootstrap-popover');
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
            precision: 4,
            editable: true
        },

        templates: {
            priceOverridden: priceOverridden,
            pricesHint: pricesHint,
            pricesHintContent: pricesHintContent
        },

        events: function() {
            var events = {};

            var eventNamespace = this.eventNamespace();
            var onShow = function(clickHandler, event) {
                var popover = $(event.target).data(Popover.DATA_KEY);
                $(popover.getTipElement()).on('click' + eventNamespace, 'a', function() {
                    popover.hide();
                    clickHandler(this);
                });
            };

            events[Popover.Event.SHOWN + ' .product-price-overridden'] = _.wrap(this.useFoundPrice.bind(this), onShow);
            events[Popover.Event.SHOWN + ' .product-tier-prices'] = _.wrap(this.setPriceFromHint.bind(this), onShow);
            events[Popover.Event.HIDDEN + ' [data-toggle=popover]'] = function(e) {
                var tip = $(e.target).data(Popover.DATA_KEY).getTipElement();
                $(tip).off(eventNamespace);
            };

            return events;
        },

        /**
         * @inheritDoc
         */
        constructor: function ProductPricesEditableView() {
            ProductPricesEditableView.__super__.constructor.apply(this, arguments);
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
            this.priceOverriddenInitialized = true;
            if (!this.options.matchedPriceEnabled || !this.options.editable) {
                return;
            }

            var priceValueSetByUser = this.getElement('priceValue').val();

            // Do not show default price if user did not set price. 0 (zero) price is considered as set price
            if (!priceValueSetByUser && !this.getElement('priceValue').data('match-price-on-null')) {
                return;
            }

            var $priceOverridden = this.createElementByTemplate('priceOverridden');

            layout.initPopover($priceOverridden);
            $priceOverridden.insertAfter(this.getElement('priceValue'));

            if (_.isEmpty(this.getElement('priceValue').val()) && this.options.matchedPriceEnabled) {
                this.getElement('priceValue').addClass('matched-price');
            }
        },

        initHint: function() {
            this.hintInitialized = true;

            if (typeof this.templates.pricesHintContent !== 'function') {
                this.templates.pricesHintContent = _.template(this.getElement('pricesHintContent').html());
            }

            var $pricesHint = this.createElementByTemplate('pricesHint');

            this.getElement('priceValue').after($pricesHint);
        },

        getHintContent: function() {
            if (_.isEmpty(this.prices)) {
                return '';
            }

            return this.templates.pricesHintContent({
                model: this.model.attributes,
                prices: this.prices,
                matchedPrice: this.findPrice(),
                clickable: this.options.editable,
                formatter: NumberFormatter
            });
        },

        renderHint: function() {
            if (!this.hintInitialized) {
                this.initHint();
            }
            return ProductPricesEditableView.__super__.renderHint.apply(this, arguments);
        },

        onPriceSetManually: function(e, options) {
            if (options.manually && this.options.matchedPriceEnabled) {
                this.getElement('priceValue').removeClass('matched-price');
            }
        },

        setPriceFromHint: function(elem) {
            this.getElement('priceValue').removeClass('matched-price');
            var $elem = $(elem);
            this.model.set('unit', $elem.data('unit'));
            this.setPriceValue($elem.data('price'));
        },

        renderPriceOverridden: function() {
            if (!this.options.matchedPriceEnabled) {
                return;
            }

            if (!this.priceOverriddenInitialized) {
                this.initPriceOverridden();
            }

            var priceValue = NumberFormatter.unformatStrict(this.model.get('price'));
            var price = this.findPriceValue();

            if (price !== null && this.calcTotalPrice(price) !== this.calcTotalPrice(priceValue)) {
                this.getElement('priceOverridden').show();
                this.getElement('priceValue').addClass('overridden-price');
            } else {
                this.getElement('priceOverridden').hide();
                this.getElement('priceValue').removeClass('overridden-price');
            }
        },

        calcTotalPrice: function(price) {
            if (price === null) {
                return price;
            }

            return NumberFormatter.formatMonetary(price);
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
