import defaultEditors from 'orodatagrid/js/inline-editing/default-editors';

export default {
    ...defaultEditors,
    'shoppinglist-line-item': require('oroshoppinglist/js/app/views/editor/shoppinglist-line-item-editor-view')
};
