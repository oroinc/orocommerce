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
});
