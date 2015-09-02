define(function(require) {
    'use strict';

    var LineItemAbstractView;
    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var layout = require('oroui/js/layout');
    var NumberFormatter = require('orolocale/js/formatter/number');
    var SubtotalsListener = require('orob2border/js/app/listener/subtotals-listener');
    var BaseView = require('oroui/js/app/views/base/view');
    var ProductUnitComponent = require('orob2bproduct/js/app/components/product-unit-component');

    /**
     * @export orob2border/js/app/views/line-item-abstract-view
     * @extends oroui.app.views.base.View
     * @class orob2border.app.views.LineItemAbstractView
     */
    LineItemAbstractView = BaseView.extend({
        /**
         * @property {Object}
         */
        options: {
            ftid: '',
            currency: null,
            selectors: {
                tierPrices: '.order-line-item-tier-prices',
                priceOverridden: '.order-line-item-price-overridden',
                tierPricesTemplate: '#order-line-item-tier-prices-template',
                productSelector: '.order-line-item-type-product input.select2',
                quantitySelector: '.order-line-item-quantity input',
                unitSelector: '.order-line-item-quantity select',
                productSku: '.order-line-item-sku .order-line-item-type-product'
            },
            disabled: false,
            freeFormUnits: null
        },

        /**
         * @property {jQuery}
         */
        $fields: null,

        /**
         * @property {jQuery}
         */
        $tierPrices: null,

        /**
         * @property {Object}
         */
        fieldsByName: null,

        /**
         * @property {Object}
         */
        tierPricesTemplate: null,

        /**
         * @property {Object}
         */
        tierPrices: null,

        /**
         * @property {Object}
         */
        matchedPrice: {},

        /**
         * @property {Object}
         */
        change: {},

        /**
         * @property {String}
         */
        lastMatchedPriceIdentifier: null,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, options || {});
            if (!this.options.ftid) {
                this.options.ftid = this.$el.data('content').toString()
                    .replace(/[^a-zA-Z0-9]+/g, '_').replace(/_+$/, '');
            }

            this.initLayout().done(_.bind(this.handleLayoutInit, this));
            this.delegate('click', '.removeLineItem', this.removeRow);
        },

        /**
         * Initialize unit loader component
         *
         * @param {Object} options
         */
        initializeUnitLoader: function(options) {
            var defaultOptions = {
                _sourceElement: this.$el,
                productSelector: this.options.selectors.productSelector,
                quantitySelector: this.options.selectors.quantitySelector,
                unitSelector: this.options.selectors.unitSelector,
                loadingMaskEnabled: false,
                dropQuantityOnLoad: false,
                defaultValues: this.options.freeFormUnits
            };

            this.subview('productUnitComponent', new ProductUnitComponent(_.extend({}, defaultOptions, options || {})));
        },

        /**
         * Doing something after loading child components
         */
        handleLayoutInit: function() {
            var self = this;

            this.$fields = this.$el.find(':input[data-ftid]');
            this.fieldsByName = {};
            this.$fields.each(function() {
                var $field = $(this);
                var name = self.normalizeName($field.data('ftid').replace(self.options.ftid + '_', ''));
                self.fieldsByName[name] = $field;
            });

            this.$tierPrices = this.$el.find(this.options.selectors.tierPrices);

            this.initTierPrices();
            this.initMatchedPrices();
            this.initProductSku();
        },

        /**
         * Convert name with "_" to name with upper case, example: some_name > someName
         *
         * @param {String} name
         *
         * @returns {String}
         */
        normalizeName: function(name) {
            name = name.split('_');
            for (var i = 1, iMax = name.length; i < iMax; i++) {
                name[i] = name[i][0].toUpperCase() + name[i].substr(1);
            }
            return name.join('');
        },

        /**
         * @param {jQuery|Array} $fields
         */
        subtotalFields: function($fields) {
            SubtotalsListener.listen($fields);
        },

        removeRow: function() {
            this.$el.trigger('content:remove');
            this.remove();
            SubtotalsListener.updateSubtotals();
        },

        initTierPrices: function() {
            this.tierPricesTemplate = _.template($(this.options.selectors.tierPricesTemplate).text());

            if (this.fieldsByName.hasOwnProperty('product')) {
                this.fieldsByName.product.change(_.bind(function(e) {
                    var productId = this._getProductId();
                    if (productId.length === 0) {
                        this.setTierPrices({});
                    } else {
                        mediator.trigger(
                            'order:load:products-tier-prices',
                            [productId],
                            _.bind(this.setTierPrices, this)
                        );
                    }
                }, this));
            }

            mediator.trigger('order:get:products-tier-prices', _.bind(this.setTierPrices, this));
            mediator.on('order:refresh:products-tier-prices', this.setTierPrices, this);
        },

        /**
         * @param {Object} tierPrices
         */
        setTierPrices: function(tierPrices) {
            var productId = this._getProductId();
            var productTierPrices = {};
            if (productId.length !== 0) {
                productTierPrices = tierPrices[productId] || {};
            }

            this.tierPrices = productTierPrices;
            this.renderTierPrices();
        },

        renderTierPrices: function() {
            var $button = this.$tierPrices.find('i');
            var content = '';
            if (!_.isEmpty(this.tierPrices)) {
                content = this.tierPricesTemplate({
                    tierPrices: this.tierPrices,
                    unitCode: this.fieldsByName.productUnit.val(),
                    matchedPrice: this.getMatchedPriceValue(),
                    clickable: this.fieldsByName.hasOwnProperty('priceValue'),
                    formatter: NumberFormatter
                });
                $button.removeClass('disabled');
            } else {
                $button.addClass('disabled');
            }

            if ($button.data('popover')) {
                $button.data('popover').options.content = content;
            } else {
                $button.data('content', content);
                layout.initPopover(this.$tierPrices);
            }
        },

        initMatchedPrices: function() {
            //skip product, productUnit always changed after product change
            this.fieldsByName.productUnit.change(_.bind(this.updateMatchedPrices, this));
            this.addFieldEvents('quantity', this.updateMatchedPrices);

            mediator.trigger('order:get:line-items-matched-prices', _.bind(this.setMatchedPrices, this));
            mediator.on('order:refresh:line-items-matched-prices', this.setMatchedPrices, this);
        },

        /**
         * @param {String} field
         * @param {Function} callback
         */
        addFieldEvents: function(field, callback) {
            this.fieldsByName[field].change(_.bind(function() {
                if (this.change[field]) {
                    clearTimeout(this.change[field]);
                }

                callback.call(this);
            }, this));

            this.fieldsByName[field].keyup(_.bind(function() {
                if (this.change[field]) {
                    clearTimeout(this.change[field]);
                }

                this.change[field] = setTimeout(_.bind(callback, this), 1500);
            }, this));
        },

        /**
         * Trigger subtotals update
         */
        updateMatchedPrices: function() {
            if (this.lastMatchedPriceIdentifier &&
                this.lastMatchedPriceIdentifier === this._getMatchedPriceIdentifier()
            ) {
                this.setMatchedPrices();
                return;
            }

            var productId = this._getProductId();
            var unitCode = this.fieldsByName.productUnit.val();
            var quantity = this.fieldsByName.quantity.val();

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
            if (matchedPrices === undefined) {
                return;
            }
            var identifier = this._getMatchedPriceIdentifier();
            if (identifier) {
                this.matchedPrice = matchedPrices[identifier] || {};
            } else {
                this.matchedPrice = {};
            }

            this.lastMatchedPriceIdentifier = identifier;
        },

        /**
         * @returns {String}
         * @private
         */
        _getProductId: function() {
            return this.fieldsByName.hasOwnProperty('product') ? this.fieldsByName.product.val() : '';
        },

        /**
         * @returns {String|Null}
         * @private
         */
        _getMatchedPriceIdentifier: function() {
            var productId = this._getProductId();
            var unitCode = this.fieldsByName.productUnit.val();
            var quantity = this.fieldsByName.quantity.val();

            return productId.length === 0 ? null : productId + '-' + unitCode + '-' + quantity;
        },

        /**
         * @returns {String|Null}
         */
        getMatchedPriceValue: function() {
            return !_.isEmpty(this.matchedPrice) ? this.matchedPrice.value : null;
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            mediator.off('order:refresh:products-tier-prices', this.setTierPrices, this);
            mediator.off('order:refresh:line-items-matched-prices', this.setMatchedPrices, this);

            LineItemAbstractView.__super__.dispose.call(this);
        },

        initProductSku: function() {
            if (this.fieldsByName.product) {
                this.fieldsByName.product.change(_.bind(function() {
                    var data = this.fieldsByName.product.select2('data') || {};
                    this.$el.find(this.options.selectors.productSku).html(data.sku || null);
                }, this));
            }
        }
    });

    return LineItemAbstractView;
});
