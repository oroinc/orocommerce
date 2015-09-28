define(function(require) {
    'use strict';

    var LineItemsView;
    var $ = require('jquery');
    var _ = require('underscore');
    var ProductsPricesComponent = require('orob2bpricing/js/app/components/products-prices-component');
    var BaseView = require('oroui/js/app/views/base/view');

    /**
     * @export orob2bsale/js/app/views/line-items-view
     * @extends oroui.app.views.base.View
     * @class orob2bsale.app.views.LineItemsView
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
                $priceList: this.$priceList,
                tierPrices: this.options.tierPrices,
                matchedPrices: this.options.matchedPrices,
                tierPricesRoute: this.options.tierPricesRoute,
                matchedPricesRoute: this.options.matchedPricesRoute
            }));

            this.initLayout().done(_.bind(this.handleLayoutInit, this));
        },

        /**
         * Doing something after loading child components
         */
        handleLayoutInit: function() {
            this.$el.find('.add-lineitem').mousedown(function() {
                $(this).click();
            });
        }
    });

    return LineItemsView;
});
