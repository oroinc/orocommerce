define(function() {
    'use strict';

    const types = {
        productServerRenderGrid: 'oroproduct/js/app/datagrid/backend-grid',
        productPageableCollection: 'oroproduct/js/app/datagrid/backend-pageable-collection'
    };

    return function(type) {
        return types[type] || null;
    };
});
