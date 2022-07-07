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
        let result = null;

        if (el.tagName === 'TR') {
            result = {
                type: this.componentType
            };
        }

        return result;
    }
});

export default TableRowTypeBuilder;
