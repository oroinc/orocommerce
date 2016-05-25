define(function (require) {
    'use strict';

    var LineItemsView;
    var $ = require('jquery');
    var _ = require('underscore');
    var ProductsPricesComponent = require('orob2bpricing/js/app/components/products-prices-component');
    var BaseView = require('oroui/js/app/views/base/view');
    var mediator = require('oroui/js/mediator');

    LineItemsView = BaseView.extend({
        /**
         * @property {Object}
         */
        options: {
            tierPrices: null,
            matchedPrices: {},
            tierPricesRoute: '',
            matchedPricesRoute: '',
            selectors: {
                lineItem: '.invoice-line-item',
                lineItemIndex: '.invoice-line-item-index',
                prototypeHolder: '.invoice-line-items tbody',
                prototypeCurrency: '[name$="[currency]"]',
                sortOrder: '[name$="[sortOrder]"]'
            },
            nextSortOrder: 1
        },

        /**
         * @inheritDoc
         */
        initialize: function (options) {
            this.options = _.defaults(options || {}, this.options);

            this.subview('productsPricesComponent', new ProductsPricesComponent({
                _sourceElement: this.$el,
                tierPrices: this.options.tierPrices,
                matchedPrices: this.options.matchedPrices,
                currency: this.options.currency,
                priceList: this.options.priceList,
                tierPricesRoute: this.options.tierPricesRoute,
                matchedPricesRoute: this.options.matchedPricesRoute
            }));

            this.$el.on('content:changed', $.proxy(this._reindexLineItems, this));
            this.$el.on('content:remove', $.proxy(this._reindexLineItems, this));
            this.$el.on('content:remove', $.proxy(this._handleRemoveItem, this));

            mediator.on('invoice-line-item:created', this._setNextSortOrder, this);
            mediator.on('update:currency', this._setPrototypeCurrency, this);

            this.initLayout();
            this.options.nextSortOrder = this._fetchLastSortOrder() + 1;
            this._reindexLineItems();
        },

        _fetchLastSortOrder: function () {
            return +this.$el.find(this.options.selectors.lineItem).last().find(this.options.selectors.sortOrder).val();
        },

        /**
         * @param {Object} e
         */
        _reindexLineItems: function (e) {
            var i = 1;
            this.$el.find(this.options.selectors.lineItem).each(_.bind(function (index, element) {
                $(element).find(this.options.selectors.lineItemIndex).text(i);
                if (!e || !(e.type == 'content:remove' && element === e.target)) {
                    i++;
                }
            }, this))
        },

        _handleRemoveItem: function () {
            mediator.trigger('line-items-totals:update');
        },

        /**
         * @param {jQuery.Element} $lineItem
         */
        _setNextSortOrder: function ($lineItem) {
            var $sortOrder = $lineItem.find(this.options.selectors.sortOrder);
            if(!$sortOrder.val()){
                $sortOrder.val(this.options.nextSortOrder);
                this.options.nextSortOrder++;
            }
        },

        /**
         * @param {String} val
         */
        _setPrototypeCurrency: function (val) {
            this.options.currency = val;
            var prototype = $(this.options.selectors.prototypeHolder).attr('data-prototype');
            var $prototype = $('<div></div>').html(prototype);
            $prototype.find(this.options.selectors.prototypeCurrency).val(val);
            var options = JSON.parse($prototype.find('tr').attr('data-page-component-options'));
            options.currency = val;
            $prototype.find('tr').attr('data-page-component-options', JSON.stringify(options));
            $(this.options.selectors.prototypeHolder).attr('data-prototype', $prototype.html());
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.$el.off('content:changed', $.proxy(this._reindexLineItems, this));
            this.$el.off('content:remove', $.proxy(this._reindexLineItems, this));
            this.$el.off('content:remove', $.proxy(this._handleRemoveItem, this));

            mediator.off('invoice-line-item:created', this._setNextSortOrder, this);
            mediator.off('update:currency', this._setPrototypeCurrency, this);

            LineItemsView.__super__.dispose.call(this);
        }
    });

    return LineItemsView;
});
