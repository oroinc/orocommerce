define(function(require) {
    'use strict';

    var ProductPricesComponent;
    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var layout = require('oroui/js/layout');
    var NumberFormatter = require('orolocale/js/formatter/number');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var ProductPricesEditableView = require('orob2bpricing/js/app/views/product-prices-editable-view');

    ProductPricesComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            selectors: {
                tierButtonTemplate: '#product-prices-tier-button-template',
                tierTableTemplate: '#product-prices-tier-table-template',
                priceOverriddenTemplate: '#product-prices-price-overridden-template'
            },
            $product: null,
            $priceValue: null,
            $priceType: null,
            $productUnit: null,
            $quantity: null,
            bundledPriceTypeValue: '20',
            disabled: false,
            isNew: false,
            precision: 4
        },

        /**
         * @property {jQuery}
         */
        $el: null,

        /**
         * @property {jQuery}
         */
        $tierButton: null,

        /**
         * @property {jQuery}
         */
        $priceOverridden: null,

        /**
         * @property {Function}
         */
        tierTableTemplate: null,

        /**
         * @property {Object}
         */
        tierPrices: null,

        /**
         * @property {Object}
         */
        matchedPrice: {},

        /**
         * @property {String}
         */
        lastMatchedPriceIdentifier: null,

        /**
         * @property {Object}
         */
        change: {},

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, options || {});
            this.priceView = new ProductPricesEditableView($.extend(true, {}, this.options, {
                el: options._sourceElement.get(0),
                productModel: options.productModel,
                modelAttr: {prices: options.prices}
            }));
            this.model = this.priceView.model;

            this.initTierPrices();
            return;
            this.initMatchedPrices();

            if (this.options.isNew) {
                this.updateTierPrices();
                this.updateMatchedPrices();
            } else {
                mediator.trigger('pricing:get:products-tier-prices', _.bind(this.setTierPrices, this));
                mediator.trigger('pricing:get:line-items-matched-prices', _.bind(this.setMatchedPrices, this));
            }
            mediator.on('update:currency', this.setCurrency, this);
        },

        initTierPrices: function() {
            mediator.on('pricing:refresh:products-tier-prices', this.setTierPrices, this);
            return;

            if (this.options.$product) {
                this.options.$product.change(_.bind(this.updateTierPrices, this));
            }

            if (_.isObject(this.options.$currency)) {
                this.options.$currency.change(_.bind(this.renderTierPrices, this));
            }

            if (this.options.$priceValue.is(':input')) {
                this.$tierButton.on('click', 'a[data-price]', _.bind(this.setPriceFromTier, this));
            }
        },

        updateTierPrices: function() {
            if (this.disposed) {
                return;
            }
            var productId = this.model.get('id');
            if (productId.length === 0) {
                this.setTierPrices({});
            } else {
                mediator.trigger(
                    'pricing:load:products-tier-prices',
                    [productId],
                    _.bind(this.setTierPrices, this)
                );
            }
        },

        /**
         * @param {Object} tierPrices
         */
        setTierPrices: function(tierPrices) {
            var productTierPrices = {};

            var productId = this.model.get('id');
            if (productId.length !== 0) {
                productTierPrices = tierPrices[productId] || {};
            }

            this.model.set('prices', productTierPrices);
        },

        /**
         * @param {Event} e
         */
        setPriceFromTier: function(e) {
            var $target = $(e.currentTarget);

            this.options.$productUnit.val($target.data('unit')).change();
            this.setPrice($target.data('price'));
        },

        /**
         * @param {Number|String} price
         */
        setPrice: function(price) {
            this.options.$priceValue.val(this.calcTotalPrice(price)).change();
        },

        renderTierPrices: function() {
            var $button = this.$tierButton.find('i');
            var content = '';
            var tierPrices = {};
            var currency = this.getCurrency();
            _.each(this.tierPrices, function(prices, unit) {
                prices = _.filter(prices, function(price) {
                    return price.currency === currency;
                });
                if (!_.isEmpty(prices)) {
                    tierPrices[unit] = prices;
                }
            });

            if (!_.isEmpty(tierPrices)) {
                content = this.tierTableTemplate({
                    tierPrices: tierPrices,
                    unitCode: this.options.$productUnit.val(),
                    clickable: this.options.$priceValue.is(':input'),
                    formatter: NumberFormatter,
                    matchedPrice: this.getMatchedPriceValue()
                });
                $button.removeClass('disabled');
            } else {
                $button.addClass('disabled');
            }

            if ($button.data('popover')) {
                $button.data('popover').options.content = content;
            } else {
                $button.data('content', content);
                layout.initPopover(this.$tierButton);
            }
        },

        initMatchedPrices: function() {
            if (this.options.$priceValue.is(':input')) {
                this.$priceOverridden = $(_.template($(this.options.selectors.priceOverriddenTemplate).text())());
                this.options.$priceValue.before(this.$priceOverridden);
                layout.initPopover(this.$priceOverridden);

                this.addFieldEvents(this.options.$priceValue, this.onPriceValueChange);

                if (_.isEmpty(this.options.$priceValue.val())) {
                    this.options.$priceValue.addClass('matched-price');
                }

                this.$priceOverridden.on('click', 'a', _.bind(this.setPriceFromMatched, this));
            }

            mediator.on('pricing:set:line-items-from-matched-prices', this.setPriceFromMatched, this);
            mediator.on('pricing:refresh:line-items-matched-prices', this.setMatchedPrices, this);
            mediator.on('pricing:collect:line-items', this.collectLineItems, this);

            //skip product, productUnit always changed after product change
            this.options.$productUnit.change(_.bind(this.updateMatchedPrices, this));
            this.addFieldEvents(this.options.$quantity, this.updateMatchedPrices);
        },

        setPriceFromMatched: function() {
            this.setPrice(this.getMatchedPriceValue());
            this.options.$priceValue.addClass('matched-price');
        },

        /**
         * @param {Array} items
         */
        collectLineItems: function(items) {
            var item = this.getLineItem();
            if (item) {
                items.push(item);
            }
        },

        /**
         * @returns {Object}
         */
        getLineItem: function() {
            var productId = this._getProductId();
            var item = null;

            if (productId.length) {
                item = {
                    product: productId,
                    unit: this.options.$productUnit.val(),
                    qty: this.options.$quantity.val(),
                    currency: this.getCurrency()
                };
            }

            return item;
        },

        /**
         * Trigger subtotals update
         */
        updateMatchedPrices: function() {
            this.options.$priceValue.trigger('value:changing');

            if (this.lastMatchedPriceIdentifier &&
                this.lastMatchedPriceIdentifier === this._getMatchedPriceIdentifier()
            ) {
                this.setMatchedPrices();
                return;
            }

            var item = this.getLineItem();

            if (!item) {
                this.setMatchedPrices({});
            } else {
                mediator.trigger('pricing:load:line-items-matched-prices', [item], _.bind(this.setMatchedPrices, this));
            }
        },

        /**
         * @param {Object} matchedPrices
         */
        setMatchedPrices: function(matchedPrices) {
            if (matchedPrices !== undefined) {
                var identifier = this._getMatchedPriceIdentifier();
                if (identifier) {
                    this.matchedPrice = matchedPrices[identifier] || {};
                } else {
                    this.matchedPrice = {};
                }

                this.lastMatchedPriceIdentifier = identifier;
            }

            this.renderTierPrices();

            if (this.options.$priceValue.is(':input')) {
                if (this.options.$priceValue.hasClass('matched-price')) {
                    this.setPrice(this.getMatchedPriceValue());
                    this.options.$priceValue.addClass('matched-price');
                } else {
                    this.renderPriceOverridden();
                    mediator.trigger('pricing:load:line-item-price-overridden', this.options.$priceValue);
                }
            } else if (!this.options.disabled) {
                var matchedPrice = this.getMatchedPriceValue();
                var price = this.options.$priceValue.data('price');

                if (!matchedPrice && parseFloat(price) > 0.0) {
                    matchedPrice = price;
                }

                this.options.$priceValue.text(NumberFormatter.formatCurrency(matchedPrice, this.getCurrency()));
            }

            this.options.$priceValue.trigger('value:changed');
        },

        /**
         * @returns {String|Null}
         * @private
         */
        _getMatchedPriceIdentifier: function() {
            var productId = this._getProductId();
            if (productId.length === 0) {
                return null;
            }

            var identifiers = [];
            identifiers.push(productId);
            identifiers.push(this.options.$productUnit.val());
            identifiers.push(this.options.$quantity.val());
            identifiers.push(this.getCurrency());

            return identifiers.join('-');
        },

        /**
         * @returns {String|Null}
         */
        getMatchedPriceValue: function() {
            return !_.isEmpty(this.matchedPrice) ? this.matchedPrice.value : null;
        },

        /**
         * @param {Number|String} price
         * @returns {Number}
         */
        calcTotalPrice: function(price) {
            var quantity = 1;
            if (this.options.$priceType && this.options.$priceType.val() === this.options.bundledPriceTypeValue) {
                quantity = parseFloat(this.options.$quantity.val());
            }

            return +(price * quantity).toFixed(this.options.precision);
        },

        onPriceValueChange: function() {
            this.options.$priceValue.removeClass('matched-price');
            this.renderPriceOverridden();
        },

        renderPriceOverridden: function() {
            var priceValue = this.options.$priceValue.val();

            if (!_.isEmpty(this.matchedPrice) &&
                this.calcTotalPrice(this.matchedPrice.value) !== parseFloat(priceValue)
            ) {
                this.$priceOverridden.show();
            } else {
                this.$priceOverridden.hide();
            }
        },

        /**
         * @returns {String}
         * @private
         */
        _getProductId: function() {
            var productId = '';
            if (this.options.$product) {
                this.options.$product.each(function() {
                    if (this.value && $(this).parent().is(':visible')) {
                        productId = this.value;
                    }
                });
            }
            return productId;
        },

        /**
         * @param {jQuery} $field
         * @param {Function} callback
         */
        addFieldEvents: function($field, callback) {
            var name = $field.attr('name');
            $field.change(_.bind(function() {
                if (this.change[name]) {
                    clearTimeout(this.change[name]);
                }

                callback.call(this);
            }, this));

            $field.keyup(_.bind(function() {
                if (this.change[name]) {
                    clearTimeout(this.change[name]);
                }

                this.change[name] = setTimeout(_.bind(callback, this), 1500);
            }, this));
        },

        /**
         * @returns {String}
         */
        getCurrency: function() {
            if (_.isObject(this.options.$currency)) {
                return this.options.$currency.val();
            } else {
                return this.options.$currency;
            }
        },

        /**
         * @param {String} value
         */
        setCurrency: function(value) {
            this.options.$currency.val(value);
            this.updateTierPrices();
            mediator.trigger('pricing:reload:products-tier-prices');
            this.trigger('currency:changed');
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            mediator.off('pricing:refresh:products-tier-prices', this.setTierPrices, this);
            mediator.off('update:currency', this.setCurrency, this);
            mediator.off('pricing:refresh:line-items-matched-prices', this.setMatchedPrices, this);

            ProductPricesComponent.__super__.dispose.call(this);
        }
    });

    return ProductPricesComponent;
});
