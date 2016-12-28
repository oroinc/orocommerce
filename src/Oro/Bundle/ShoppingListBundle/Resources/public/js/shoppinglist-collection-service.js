define(function(require) {
    'use strict';

    var shoppingListCollectionService;
    var $ = require('jquery');

    shoppingListCollectionService = {
        shoppingListCollection: $.Deferred()
    };

    return shoppingListCollectionService;
});
