const types = {
    productServerRenderGrid: 'oroproduct/js/app/datagrid/backend-grid',
    productPageableCollection: 'oroproduct/js/app/datagrid/backend-pageable-collection'
};

export default function(type) {
    return types[type] || null;
};
