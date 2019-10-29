define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const ProductsPricesComponent = require('oropricing/js/app/components/products-prices-component');
    const BaseView = require('oroui/js/app/views/base/view');
    const mediator = require('oroui/js/mediator');

    const LineItemsView = BaseView.extend({
        /**
         * @property {Object}
         */
        options: {
            tierPrices: null,
            tierPricesRoute: '',
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
        constructor: function LineItemsView(options) {
            LineItemsView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            this.subview('productsPricesComponent', new ProductsPricesComponent({
                _sourceElement: this.$el,
                tierPrices: this.options.tierPrices,
                currency: this.options.currency,
                tierPricesRoute: this.options.tierPricesRoute
            }));

            this._reindexLineItems = this._reindexLineItems.bind(this);
            this._handleRemoveItem = this._handleRemoveItem.bind(this);

            this.$el.on('content:changed', this._reindexLineItems);
            this.$el.on('content:remove', this._reindexLineItems);
            this.$el.on('content:remove', this._handleRemoveItem);

            mediator.on('invoice-line-item:created', this._setNextSortOrder, this);
            mediator.on('update:currency', this._setPrototypeCurrency, this);

            this.initLayout();
            this.options.nextSortOrder = this._fetchLastSortOrder() + 1;
            this._reindexLineItems();
        },

        _fetchLastSortOrder: function() {
            return +this.$el.find(this.options.selectors.lineItem).last().find(this.options.selectors.sortOrder).val();
        },

        /**
         * @param {Object} e
         */
        _reindexLineItems: function(e) {
            let i = 1;
            this.$el.find(this.options.selectors.lineItem).each(_.bind(function(index, element) {
                $(element).find(this.options.selectors.lineItemIndex).text(i);
                if (!e || !(e.type === 'content:remove' && element === e.target)) {
                    i++;
                }
            }, this));
        },

        _handleRemoveItem: function() {
            mediator.trigger('line-items-totals:update');
        },

        /**
         * @param {jQuery.Element} $lineItem
         */
        _setNextSortOrder: function($lineItem) {
            const $sortOrder = $lineItem.find(this.options.selectors.sortOrder);
            if (!$sortOrder.val()) {
                $sortOrder.val(this.options.nextSortOrder);
                this.options.nextSortOrder++;
            }
        },

        /**
         * @param {String} val
         */
        _setPrototypeCurrency: function(val) {
            this.options.currency = val;
            const prototype = $(this.options.selectors.prototypeHolder).attr('data-prototype');
            const $prototype = $('<div></div>').html(prototype);
            $prototype.find(this.options.selectors.prototypeCurrency).val(val);
            const options = JSON.parse($prototype.find('tr').attr('data-page-component-options'));
            options.currency = val;
            $prototype.find('tr').attr('data-page-component-options', JSON.stringify(options));
            $(this.options.selectors.prototypeHolder).attr('data-prototype', $prototype.html());
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.$el.off('content:changed', this._reindexLineItems);
            this.$el.off('content:remove', this._reindexLineItems);
            this.$el.off('content:remove', this._handleRemoveItem);

            mediator.off('invoice-line-item:created', this._setNextSortOrder, this);
            mediator.off('update:currency', this._setPrototypeCurrency, this);

            LineItemsView.__super__.dispose.call(this);
        }
    });

    return LineItemsView;
});
