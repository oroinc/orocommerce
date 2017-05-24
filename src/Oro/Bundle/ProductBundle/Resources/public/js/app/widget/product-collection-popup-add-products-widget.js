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
        requiredOptions: ['eventName', 'hiddenProductsSelector'],

        /**
         * @property {Array}
         */
        currentSelection: null,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            ProductCollectionPopupAddProductsWidget.__super__.initialize.apply(this, arguments);

            this._checkOptions();

            this.getAction('addProducts', 'adopted', _.bind(function(actionElement) {
                actionElement.on('click', _.bind(this._triggerEventAndClose, this));
            }, this));

            mediator.on('grid_load:complete', this._addOnSelectListener, this);
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
         * @param {PageableCollection} collection
         * @param {jQuery} $grid
         *
         * @private
         */
        _addOnSelectListener: function(collection, $grid) {
            if (this.$el.has($grid).length) {
                this.listenTo(collection, 'backgrid:selected', _.bind(function() {
                    this._updateSelection(collection);
                    this._updateActionButtonView();
                }, this));
            }
        },

        /**
         * @param {PageableCollection} collection
         *
         * @private
         */
        _updateSelection: function(collection) {
            var selection = {};
            collection.trigger('backgrid:getSelected', selection);
            this.currentSelection = selection.selected;
        },

        /**
         * @private
         */
        _updateActionButtonView: function() {
            var currentSelection = this.currentSelection;

            this.getAction('addProducts', 'adopted', function(actionElement) {
                var disabled = _.isEmpty(currentSelection);
                actionElement
                    .prop('disabled', disabled)
                    .toggleClass('disabled', disabled);
            });
        },

        /**
         * @private
         */
        _triggerEventAndClose: function() {
            mediator.trigger(this.options.eventName, this.currentSelection);
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
            if (!arguments.length) {
                var oldFirstRun = this.firstRun;
                this.firstRun = false;
                ProductCollectionPopupAddProductsWidget.__super__.loadContent.call(this, undefined, 'post');
                this.firstRun = oldFirstRun;
            } else {
                ProductCollectionPopupAddProductsWidget.__super__.loadContent.apply(this, arguments);
            }
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            mediator.off('grid_load:complete', this._addOnSelectListener, this);

            ProductCollectionPopupAddProductsWidget.__super__.dispose.call(this);
        }
    });

    return ProductCollectionPopupAddProductsWidget;
});
