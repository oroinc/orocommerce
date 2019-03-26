define(function(require) {
    'use strict';

    var RelatedItemsTabsComponent;
    var _ = require('underscore');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var BaseCollection = require('oroui/js/app/models/base/collection');
    var TabCollectionView = require('oroui/js/app/views/tab-collection-view');
    var $ = require('jquery');

    RelatedItemsTabsComponent = BaseComponent.extend({

        /**
         * @inheritDoc
         */
        constructor: function RelatedItemsTabsComponent() {
            RelatedItemsTabsComponent.__super__.constructor.apply(this, arguments);
        },

        /**
         * @param {Object} options
         * @param {Array<Object>} options.data collection of tabs build over entities category
         */
        initialize: function(options) {
            if (!options.data.length) {
                // Nothing to show
                return;
            }

            var categories = _.each(options.data, function(item) {
                item.uniqueId = _.uniqueId(item.id);
            });

            this.categories = new BaseCollection(categories);
            var firstElement = this.categories.first();
            firstElement.set('active', true);

            this.view = new TabCollectionView({
                el: options._sourceElement,
                animationDuration: 0,
                collection: this.categories,
                useDropdown: options.useDropdown
            });

            this._hideAllGrids();
            this._showGrid(firstElement.id);

            this.listenTo(this.categories, 'change', this.onTabChange);
        },

        onTabChange: function(model) {
            this._hideAllGrids();

            if (model.hasChanged('active') && model.get('active') === true) {
                this._showGrid(model.id);
            }
        },

        _getGrid: function(gridName) {
            return $('#' + gridName);
        },

        _showGrid: function(gridName) {
            var $grid = this._getGrid(gridName);

            $grid.show();
        },

        _hideGrid: function(gridName) {
            this._getGrid(gridName).hide();
        },

        _hideAllGrids: function() {
            this.categories.each(function(category) {
                this._hideGrid(category.id);
            }, this);
        }
    });
    return RelatedItemsTabsComponent;
});
