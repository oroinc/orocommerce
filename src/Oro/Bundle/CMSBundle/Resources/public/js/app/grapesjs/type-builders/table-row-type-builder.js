import BaseTypeBuilder from 'orocms/js/app/grapesjs/type-builders/base-type-builder';
import TableTypeDecorator from '../controls/table-edit/table-type-decorator';

const TableRowTypeBuilder = BaseTypeBuilder.extend({
    parentType: 'row',

    modelMixin: {
        defaults: {
            removable: false,
            copyable: false,
            draggable: ['tbody', 'thead', 'tfoot']
        },

        ...TableTypeDecorator
    },

    constructor: function TableRowTypeBuilder(...args) {
        TableRowTypeBuilder.__super__.constructor.apply(this, args);
    },

    isComponent(el) {
        return el.nodeType === el.ELEMENT_NODE && el.tagName === 'TR';
    }
});

export default TableRowTypeBuilder;
