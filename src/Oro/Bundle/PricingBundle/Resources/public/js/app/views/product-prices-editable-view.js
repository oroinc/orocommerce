define(function(require) {
    'use strict';

    const pricesHint = require('tpl-loader!oropricing/templates/product/prices-tier-button.html');
    const pricesHintContent = require('tpl-loader!oropricing/templates/product/prices-tier-table.html');
    const priceOverridden = require('tpl-loader!oropricing/templates/product/prices-price-overridden.html');
    const BaseProductPricesView = require('oropricing/js/app/views/base-product-prices-view');
    const NumberFormatter = require('orolocale/js/formatter/number');
    const Popover = require('bootstrap-popover');
    const layout = require('oroui/js/layout');
    const $ = require('jquery');
    const _ = require('underscore');
    const mediator = require('oroui/js/mediator');
    const pricesHelper = require('oropricing/js/app/prices-helper');

    const ProductPricesEditableView = BaseProductPricesView.extend({
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
            editable: true,
            ariaControlsId: null
        },

        templates: {
            priceOverridden: priceOverridden,
            pricesHint: pricesHint,
            pricesHintContent: pricesHintContent
        },

        events: function() {
            const events = {};

            const eventNamespace = this.eventNamespace();
            const onShow = function(clickHandler, event) {
                const popover = $(event.target).data(Popover.DATA_KEY);
                $(popover.getTipElement())
                    .off(eventNamespace)
                    .on('click' + eventNamespace, 'a', function(e) {
                        e.preventDefault();
                        popover.hide();
                        clickHandler(this);
                    });
            };

            events[Popover.Event.SHOWN + ' .product-price-overridden'] = _.wrap(this.useFoundPrice.bind(this), onShow);
            events[Popover.Event.SHOWN + ' .product-tier-prices'] = _.wrap(this.setPriceFromHint.bind(this), onShow);
            events[Popover.Event.HIDDEN + ' [data-toggle=popover]'] = function(e) {
                const tip = $(e.target).data(Popover.DATA_KEY).getTipElement();
                $(tip).off(eventNamespace);
            };

            return events;
        },

        /**
         * @inheritdoc
         */
        constructor: function ProductPricesEditableView(options) {
            ProductPricesEditableView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, _.pick(options, _.keys(this.options)));
            this.templates = $.extend(true, {}, this.templates, options.templates || {});

            ProductPricesEditableView.__super__.initialize.call(this, options);
        },

        /**
         * @inheritdoc
         */
        deferredInitialize: function(options) {
            ProductPricesEditableView.__super__.deferredInitialize.call(this, options);
        },

        /**
         * @inheritdoc
         */
        dispose: function(options) {
            delete this.templates;
            ProductPricesEditableView.__super__.dispose.call(this);
        },

        /**
         * @inheritdoc
         */
        findPrice: function(...args) {
            const price = ProductPricesEditableView.__super__.findPrice.apply(this, args);
            this.model.set('found_price', price);
            this.getElement('priceValue').data('found_price', price);
            return price;
        },

        /**
         * @inheritdoc
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
            this.getElement('priceValue').trigger('change');
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

            const priceValueSetByUser = this.getElement('priceValue').val();

            // Do not show default price if user did not set price. 0 (zero) price is considered as set price
            if (!priceValueSetByUser && !this.getElement('priceValue').data('match-price-on-null')) {
                return;
            }

            const $priceOverridden = this.createElementByTemplate('priceOverridden');

            layout.initPopover($priceOverridden);
            $priceOverridden.insertAfter(this.getElement('priceValue'));

            if (_.isEmpty(this.getElement('priceValue').val()) && this.options.matchedPriceEnabled) {
                this.unlockPrice();
            }
        },

        initHint: function() {
            this.hintInitialized = true;

            if (typeof this.templates.pricesHintContent !== 'function') {
                this.templates.pricesHintContent = _.template(this.getElement('pricesHintContent').html());
            }

            const $pricesHint = this.createElementByTemplate('pricesHint');
            const sku = this.model.get('sku');
            const updateAriaLabel = value => {
                $pricesHint.attr('aria-label', _.__('oro.pricing.view_all_prices_extended', {
                    product_attrs: value
                }));
            };

            if (sku) {
                updateAriaLabel(sku);
            }

            this.model.on('change:sku', (model, newValue) => updateAriaLabel(newValue));
            this.getElement('priceValue').after($pricesHint);
        },

        getHintContent: function() {
            if (_.isEmpty(this.prices)) {
                return '';
            }

            return this.templates.pricesHintContent({
                model: this.model.toJSON(),
                prices: pricesHelper.sortUnitPricesByLowQuantity(this.prices),
                matchedPrice: this.findPrice(),
                clickable: this.options.editable,
                formatter: NumberFormatter,
                ariaControlsId: this.options.ariaControlsId
            });
        },

        renderHint: function() {
            if (!this.hintInitialized) {
                this.initHint();
            }
            return ProductPricesEditableView.__super__.renderHint.call(this);
        },

        onPriceSetManually: function(e, options) {
            if (options.manually && this.options.matchedPriceEnabled) {
                this.lockPrice();
            }
        },

        setPriceFromHint: function(elem) {
            this.lockPrice();
            const $elem = $(elem);
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

            const priceValue = NumberFormatter.unformatStrict(this.model.get('price'));
            const price = this.findPriceValue();

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
            this.unlockPrice();
        },

        lockPrice: function() {
            this.getElement('priceValue').removeClass('matched-price');
            mediator.trigger('pricing:product-price:lock', this.getElement('priceValue'));
        },

        unlockPrice: function() {
            this.getElement('priceValue').addClass('matched-price');
            mediator.trigger('pricing:product-price:unlock', this.getElement('priceValue'));
        }
    });

    return ProductPricesEditableView;
});
