import _ from 'underscore';
import BaseComponent from 'oroui/js/app/components/base/component';
import BaseCollection from 'oroui/js/app/models/base/collection';
import TabCollectionView from 'oroui/js/app/views/tab-collection-view';
import $ from 'jquery';

const RelatedItemsTabsComponent = BaseComponent.extend({

    /**
     * @inheritdoc
     */
    constructor: function RelatedItemsTabsComponent(options) {
        RelatedItemsTabsComponent.__super__.constructor.call(this, options);
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

        const categories = _.each(options.data, function(item) {
            item.uniqueId = _.uniqueId(item.id);
        });

        this.categories = new BaseCollection(categories);
        const firstElement = this.categories.first();
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
        const $grid = this._getGrid(gridName);

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
export default RelatedItemsTabsComponent;
