require([
    'oroui/js/app/controllers/base/controller',
    'oroui/js/tools'
], function(BaseController, tools) {
    'use strict';

    /**
     * Init Favorite related views
     */
    BaseController.loadBeforeAction([
        'jquery',
        'oronavigation/js/app/components/favorite-component',
        'oronavigation/js/app/models/base/model',
        'oronavigation/js/app/models/base/collection'
    ], function($, FavoriteComponent, Model, Collection) {
        var collection;

        collection = new Collection([], {
            model: Model
        });

        BaseController.addToReuse('frontendFavoritePage', FavoriteComponent, {
            dataSource: '#frontend_favorite-content [data-data]',
            buttonOptions: {
                el: '#bookmark-buttons .frontend-favorite-button',
                navigationElementType: 'favoriteButton'
            },
            tabItemTemplate: $('#template-dot-menu-item').html(),
            tabOptions: {
                el: '#frontend_favorite-content',
                listSelector: '.extra-list',
                fallbackSelector: '.dot-menu-empty-message'
            },
            collection: collection
        });
    });

    /**
     * Init PageHistoryView
     */
    BaseController.loadBeforeAction([
        'oronavigation/js/app/views/history-view'
    ], function(PageHistoryView) {
        BaseController.addToReuse('frontend_history', PageHistoryView, {
            el: '#frontend_history-content',
            dataItems: 'frontend_history'
        });
    });

    /**
     * Init PageMostViewedView
     */
    BaseController.loadBeforeAction([
        'oronavigation/js/app/views/most-viewed-view'
    ], function(PageMostViewedView) {
        BaseController.addToReuse('frontend_mostviewed', PageMostViewedView, {
            el: '#frontend_mostviewed-content',
            dataItems: 'frontend_mostviewed'
        });
    });
});
