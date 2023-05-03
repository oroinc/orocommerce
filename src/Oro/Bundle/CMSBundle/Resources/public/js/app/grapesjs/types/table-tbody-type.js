import __ from 'orotranslation/js/translator';
import BaseType from 'orocms/js/app/grapesjs/types/base-type';
import TableTypeDecorator from '../controls/table-edit/table-type-decorator';

const TableTbodyType = BaseType.extend({
    parentType: 'tbody',

    modelProps: {
        defaults: {
            removable: false,
            copyable: false,
            draggable: false,
            name: __('oro.cms.wysiwyg.component.table_body.label')
        },

        ...TableTypeDecorator
    },

    constructor: function TableTbodyTypeBuilder(...args) {
        TableTbodyTypeBuilder.__super__.constructor.apply(this, args);
    },

    isComponent(el) {
        return el.nodeType === el.ELEMENT_NODE && el.tagName === 'TBODY';
    }
}, {
    type: 'tbody'
});

export default TableTbodyType;
