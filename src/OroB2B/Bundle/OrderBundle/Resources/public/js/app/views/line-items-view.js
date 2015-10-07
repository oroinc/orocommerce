define(function(require) {
    'use strict';

    var LineItemsView;
    var $ = require('jquery');
    var _ = require('underscore');
    var ProductsPricesComponent = require('orob2bpricing/js/app/components/products-prices-component');
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
            matchedPrices: {},
            tierPricesRoute: '',
            matchedPricesRoute: '',
            currency: null
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
            this.$priceList = this.$form.find(':input[name$="[priceList]"]');
            this.$currency = this.$form.find(':input[data-ftid="' + this.$form.attr('name') + '_currency"]');

            this.$el.find('.add-list-item').mousedown(function(e) {
                $(this).click();
            });

            this.subview('productsPricesComponent', new ProductsPricesComponent({
                _sourceElement: this.$el,
                tierPrices: this.options.tierPrices,
                matchedPrices: this.options.matchedPrices,
                $currency: this.$currency.length ? this.$currency : this.options.currency,
                $priceList: this.$priceList,
                tierPricesRoute: this.options.tierPricesRoute,
                matchedPricesRoute: this.options.matchedPricesRoute
            }));
        }
    });

    return LineItemsView;
});
