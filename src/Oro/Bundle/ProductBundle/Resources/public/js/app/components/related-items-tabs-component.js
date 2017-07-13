define(function(require) {
    'use strict';

    var RelatedItemsTabsComponent;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var BaseCollection = require('oroui/js/app/models/base/collection');
    var TabCollectionView = require('oroui/js/app/views/tab-collection-view');

    RelatedItemsTabsComponent = BaseComponent.extend({
        /**
         * @param {Object} options
         * @param {Array<Object>} options.data collection of tabs build over entities category
         */
        initialize: function(options) {
            var categories  = options.data;

            if (categories.length === 0) {
                //Nothing to show
                return;
            }

            categories[0]['active'] = true;

            this.categories = new BaseCollection(categories);

            this.view = new TabCollectionView({
                el: options._sourceElement,
                animationDuration: 0,
                collection: this.categories,
                useDropdown: options.useDropdown
            });

            this._hideAllGrids(categories);
            this._showGrid(categories[0].id);

            this.listenTo(this.categories, 'change', this.onTabChange);
        },

        onTabChange: function(model) {
            this._hideAllGrids(model.collection.toArray());

            if (model.hasChanged('active') && model.get('active') === true) {
                this._showGrid(model.id);
            }
        },

        _getGrid: function(gridName) {
            return $('[data-page-component-name=' + gridName + ']');
        },

        _showGrid: function(grid) {
            this._getGrid(grid).show();
        },

        _hideGrid: function(grid) {
            this._getGrid(grid).hide();
        },

        _hideAllGrids: function(categories) {
            var self = this;

            $.each(categories, function(index, value) {
                self._hideGrid(value.id);
            });
        }
    });
    return RelatedItemsTabsComponent;
});
