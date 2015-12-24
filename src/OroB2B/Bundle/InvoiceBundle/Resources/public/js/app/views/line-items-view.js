define(function(require) {
    'use strict';

    var LineItemsView;
    var $ = require('jquery');
    var _ = require('underscore');
    var ProductsPricesComponent = require('orob2bpricing/js/app/components/products-prices-component');
    var BaseView = require('oroui/js/app/views/base/view');

    /**
     * @export orob2binvoice/js/app/views/line-items-view
     * @extends oroui.app.views.base.View
     * @class orob2binvoice.app.views.LineItemsView
     */
    LineItemsView = BaseView.extend({
        /**
         * @property {Object}
         */
        options: {
            tierPrices: null,
            matchedPrices: {},
            tierPricesRoute: '',
            matchedPricesRoute: ''
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
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, options || {});

            this.$form = this.$el.closest('form');
            this.$priceList = this.$form.find(':input[name$="[priceList]"]');

            this.subview('productsPricesComponent', new ProductsPricesComponent({
                tierPrices: this.options.tierPrices,
                matchedPrices: this.options.matchedPrices,
                tierPricesRoute: this.options.tierPricesRoute,
                matchedPricesRoute: this.options.matchedPricesRoute
            }));

            this.$el.on('content:changed', _.bind(this._onAddLineItem, this));
            this.$el.on('content:remove', _.bind(this._onRemoveLineItem, this));

            this.initLayout();
        },

        _onAddLineItem: function () {
            var lineItems, index;

            lineItems = this.$el.find('.invoice-line-item');
            index = +lineItems.last().prev().find('.invoice-line-item-index').text() + 1;
            lineItems.last().find('.invoice-line-item-index').text(index);
        },

        _onRemoveLineItem: function (e) {
            var lineItems;

            lineItems = this.$el.find('.invoice-line-item');

            var i = 1;
            lineItems.each(function (index, element) {
                if(element === e.target) {
                    return;
                }
                $(element).find('.invoice-line-item-index').text(i);
                i++
            })
        }


    });

    return LineItemsView;
});
