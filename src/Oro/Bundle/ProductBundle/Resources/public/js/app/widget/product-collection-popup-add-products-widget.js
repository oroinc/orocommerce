define(function(require) {
    'use strict';

    var ProductCollectionPopupAddProductsWidget;
    var DialogWidget = require('oro/dialog-widget');
    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');

    /**
     * This widget is responsible for triggering appropriate event given in options and passing array of products
     * selected in grid to this event.
     */
    ProductCollectionPopupAddProductsWidget = DialogWidget.extend({
        /**
         * @property {Array}
         */
        requiredOptions: ['gridName', 'hiddenProductsSelector'],

        /**
         * @inheritDoc
         */
        constructor: function ProductCollectionPopupAddProductsWidget() {
            ProductCollectionPopupAddProductsWidget.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            ProductCollectionPopupAddProductsWidget.__super__.initialize.apply(this, arguments);

            this._checkOptions();

            this.getAction('addProducts', 'adopted', _.bind(function(actionElement) {
                actionElement.on('click', _.bind(this._triggerEvent, this));
            }, this));

            mediator.on('product-collection-add-to-excluded', this._closeDialogWidget, this);
            mediator.on('product-collection-add-to-included', this._closeDialogWidget, this);
        },

        /**
         * @private
         */
        _checkOptions: function() {
            var requiredMissed = this.requiredOptions.filter(_.bind(function(option) {
                return _.isUndefined(this.options[option]);
            }, this));
            if (requiredMissed.length) {
                throw new TypeError('Missing required option(s): ' + requiredMissed.join(', '));
            }
        },

        /**
         * @private
         */
        _triggerEvent: function() {
            mediator.trigger('get-selected-products-mass-action-run:' + this.options.gridName);
        },

        /**
         * @private
         */
        _closeDialogWidget: function() {
            this.remove();
        },

        /**
         * @private
         */
        _getWidgetData: function() {
            var widgetData = ProductCollectionPopupAddProductsWidget.__super__._getWidgetData.call(this);
            var val = $(this.options.hiddenProductsSelector).val();

            if (val) {
                widgetData.hiddenProducts = val;
            }

            return widgetData;
        },

        /**
         * @inheritDoc
         */
        loadContent: function() {
            if (arguments.length) {
                ProductCollectionPopupAddProductsWidget.__super__.loadContent.apply(this, arguments);
            } else {
                var oldFirstRun = this.firstRun;
                this.firstRun = false;
                ProductCollectionPopupAddProductsWidget.__super__.loadContent.call(this, undefined, 'post');
                this.firstRun = oldFirstRun;
            }
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            mediator.off(null, null, this);

            ProductCollectionPopupAddProductsWidget.__super__.dispose.call(this);
        }
    });

    return ProductCollectionPopupAddProductsWidget;
});
