define(function(require) {
    'use strict';

    var LineItemsView;
    var $ = require('jquery');
    var _ = require('underscore');
    var routing = require('routing');
    var mediator = require('oroui/js/mediator');
    var BaseView = require('oroui/js/app/views/base/view');

    /**
     * @export orob2border/js/app/views/line-items-view
     * @extends oroui.app.views.base.View
     * @class orob2border.app.views.LineItemsView
     */
    LineItemsView = BaseView.extend({
        /**
         * @property {Object}
         */
        options: {
            tierPrices: null,
            tierPricesRoute: 'orob2b_product_price_by_pricelist'
        },

        /**
         * @property {jQuery}
         */
        $form: null,

        /**
         * @property {jQuery}
         */
        $priceList: null,

        /**
         * @property {jQuery}
         */
        $currency: null,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, options || {});
            this.initLayout().done(_.bind(this.handleLayoutInit, this));
        },

        /**
         * Doing something after loading child components
         */
        handleLayoutInit: function() {
            this.$form = this.$el.closest('form');
            this.$priceList = this.$form.find(':input[data-ftid="' + this.$form.attr('name') + '_priceList"]');
            this.$currency = this.$form.find(':input[data-ftid="' + this.$form.attr('name') + '_currency"]');

            mediator.on('order:get:products-tier-prices', this.getProductsTierPrices, this);
            mediator.on('order:load:products-tier-prices', this.loadProductsTierPrices, this);
        },

        /**
         * @param {Function} callback
         */
        getProductsTierPrices: function(callback) {
            callback(this.options.tierPrices);
        },

        /**
         * @param {Array} products
         * @param {Function} callback
         */
        loadProductsTierPrices: function(products, callback) {
            var url = routing.generate(this.options.tierPricesRoute, {
                product_ids: products,
                price_list_id: this.$priceList.val(),
                currency: this.$currency.val()
            });

            $.get(url, function(response) {
                callback(response);
            });
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            mediator.off('order:get:products-tier-prices', this.getProductsTierPrices, this);
            mediator.off('order:load:products-tier-prices', this.loadProductsTierPrices, this);

            LineItemsView.__super__.dispose.call(this);
        }
    });

    return LineItemsView;
});
