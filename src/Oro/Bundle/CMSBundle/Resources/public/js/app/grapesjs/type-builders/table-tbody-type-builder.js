import BaseTypeBuilder from 'orocms/js/app/grapesjs/type-builders/base-type-builder';
import TableTypeDecorator from '../controls/table-edit/table-type-decorator';

const TableTbodyTypeBuilder = BaseTypeBuilder.extend({
    parentType: 'tbody',

    modelMixin: {
        defaults: {
            removable: false,
            copyable: false,
            draggable: false
        },

        ...TableTypeDecorator
    },

    constructor: function TableTbodyTypeBuilder(...args) {
        TableTbodyTypeBuilder.__super__.constructor.apply(this, args);
    },

    isComponent(el) {
        return el.nodeType === el.ELEMENT_NODE && el.tagName === 'TBODY';
    }
});

export default TableTbodyTypeBuilder;
