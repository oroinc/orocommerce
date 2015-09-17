define(function(require) {
    'use strict';

    var ProductPricesComponent;
    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var layout = require('oroui/js/layout');
    var NumberFormatter = require('orolocale/js/formatter/number');
    var BaseComponent = require('oroui/js/app/components/base/component');

    ProductPricesComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            selectors: {
                tierButtonTemplate: '#product-prices-tier-button-template',
                tierTableTemplate: '#product-prices-tier-table-template',
                matchedButtonTemplate: '#product-prices-matched-button-template'
            },
            $product: null,
            $priceValue: null,
            $priceType: null,
            $productUnit: null,
            $quantity: null,
            bundledPriceTypeValue: '20'
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
        $matchedButton: null,

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
            this.$el = this.options._sourceElement;

            this.initTierPrices();
            this.initMatchedPrices();
        },

        initTierPrices: function() {
            this.tierTableTemplate = _.template($(this.options.selectors.tierTableTemplate).text());
            this.$tierButton = $($(this.options.selectors.tierButtonTemplate).text());
            this.options.$priceValue.after(this.$tierButton);

            mediator.trigger('pricing:get:products-tier-prices', _.bind(this.setTierPrices, this));
            mediator.on('pricing:refresh:products-tier-prices', this.setTierPrices, this);

            if (this.options.$product) {
                this.options.$product.change(_.bind(function() {
                    var productId = this._getProductId();
                    if (productId.length === 0) {
                        this.setTierPrices({});
                    } else {
                        mediator.trigger(
                            'pricing:load:products-tier-prices',
                            [productId],
                            _.bind(this.setTierPrices, this)
                        );
                    }
                }, this));
            }

            this.$tierButton.on('click', 'a[data-price]', _.bind(function(e) {
                var $target = $(e.currentTarget);
                var priceType = this.options.$priceType.val();
                var priceValue = $target.data('price');
                var quantity = 1;

                if (priceType === this.options.bundledPriceTypeValue) {
                    quantity = parseFloat(this.options.$quantity.val());
                }

                this.options.$productUnit
                    .val($target.data('unit'))
                    .change();
                this.options.$priceValue
                    .val(priceValue * quantity)
                    .change();
            }, this));
        },

        initMatchedPrices: function() {
            this.$matchedButton = $(_.template($(this.options.selectors.matchedButtonTemplate).text())());
            this.options.$priceValue.before(this.$matchedButton);
            layout.initPopover(this.$matchedButton);

            //skip product, productUnit always changed after product change
            this.options.$productUnit.change(_.bind(this.updateMatchedPrices, this));
            this.addFieldEvents(this.options.$quantity, this.updateMatchedPrices);

            mediator.trigger('order:get:line-items-matched-prices', _.bind(this.setMatchedPrices, this));
            mediator.on('order:refresh:line-items-matched-prices', this.setMatchedPrices, this);

            if (_.isEmpty(this.options.$priceValue.val())) {
                this.options.$priceValue.addClass('matched-price');
            }
            this.addFieldEvents(this.options.$priceValue, this.onPriceValueChange);

            this.$matchedButton.on('click', 'a', _.bind(function() {
                this.options.$priceValue
                    .val(this.getMatchedPriceValue())
                    .change()
                    .addClass('matched-price');
            }, this));
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

            var productId = this._getProductId();
            var unitCode = this.options.$productUnit.val();
            var quantity = this.options.$quantity.val();

            if (productId.length === 0) {
                this.setMatchedPrices({});
            } else {
                mediator.trigger(
                    'order:load:line-items-matched-prices',
                    [{'product': productId, 'unit': unitCode, 'qty': quantity}],
                    _.bind(this.setMatchedPrices, this)
                );
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

            if (this.options.$priceValue.hasClass('matched-price')) {
                this.options.$priceValue
                    .val(this.getMatchedPriceValue())
                    .change()
                    .addClass('matched-price');
            } else {
                this.renderPriceOverridden();
            }

            if (!this.options.disabled && this.initialized) {
                var matchedPrice = this.getMatchedPriceValue();
                var price = this.$priceValueText.data('price');

                if (!matchedPrice && parseFloat(price) > 0.0) {
                    matchedPrice = price;
                }

                this.$priceValueText.text(NumberFormatter.formatCurrency(matchedPrice, this.options.currency));
            }

            this.renderTierPrices();

            this.options.$priceValue.trigger('value:changed');
        },

        /**
         * @returns {String|Null}
         * @private
         */
        _getMatchedPriceIdentifier: function() {
            var productId = this._getProductId();
            var unitCode = this.options.$productUnit.val();
            var quantity = this.options.$quantity.val();

            return productId.length === 0 ? null : productId + '-' + unitCode + '-' + quantity;
        },

        /**
         * @returns {String|Null}
         */
        getMatchedPriceValue: function() {
            return !_.isEmpty(this.matchedPrice) ? this.matchedPrice.value : null;
        },

        /**
         * @param {Object} tierPrices
         */
        setTierPrices: function(tierPrices) {
            var productTierPrices = {};

            var productId = this._getProductId();
            if (productId.length !== 0) {
                productTierPrices = tierPrices[productId] || {};
            }

            this.tierPrices = productTierPrices;
            this.renderTierPrices();
        },

        onPriceValueChange: function() {
            this.options.$priceValue.removeClass('matched-price');

            this.renderPriceOverridden();
        },

        /**
         * @returns {String|null}
         * @private
         */
        _getProductId: function() {
            return this.options.$product ? this.options.$product.val() : '';
        },

        renderTierPrices: function() {
            var $button = this.$tierButton.find('i');
            var content = '';
            if (!_.isEmpty(this.tierPrices)) {
                content = this.tierTableTemplate({
                    tierPrices: this.tierPrices,
                    unitCode: this.options.$productUnit.val(),
                    clickable: this.options.$priceValue.is(':input'),
                    formatter: NumberFormatter,
                    matchedPrice: null
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

        renderPriceOverridden: function() {
            var priceValue = this.options.$priceValue.val();

            if (!_.isEmpty(this.matchedPrice) &&
                priceValue &&
                parseFloat(this.matchedPrice.value) !== parseFloat(priceValue)
            ) {
                this.$matchedButton.show();
            } else {
                this.$matchedButton.hide();
            }
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
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            mediator.on('pricing:refresh:products-tier-prices', this.setTierPrices, this);
            mediator.off('order:refresh:line-items-matched-prices', this.setMatchedPrices, this);

            ProductPricesComponent.__super__.dispose.call(this);
        }
    });

    return ProductPricesComponent;
});
