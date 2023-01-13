import __ from 'orotranslation/js/translator';
import BaseType from 'orocms/js/app/grapesjs/types/base-type';
import TableTypeDecorator from '../controls/table-edit/table-type-decorator';

const TableTheadType = BaseType.extend({
    parentType: 'thead',

    modelProps: {
        defaults: {
            removable: false,
            copyable: false,
            draggable: false,
            name: __('oro.cms.wysiwyg.component.table_head.label')
        },

        ...TableTypeDecorator
    },

    constructor: function TableTheadTypeBuilder(...args) {
        TableTheadTypeBuilder.__super__.constructor.apply(this, args);
    },

    isComponent(el) {
        return el.nodeType === el.ELEMENT_NODE && el.tagName === 'THEAD';
    }
}, {
    type: 'thead'
});

export default TableTheadType;
