import __ from 'orotranslation/js/translator';
import BaseType from 'orocms/js/app/grapesjs/types/base-type';
import TableTypeDecorator from '../controls/table-edit/table-type-decorator';

const TableRowType = BaseType.extend({
    parentType: 'row',

    modelProps: {
        defaults: {
            removable: false,
            copyable: false,
            draggable: ['tbody', 'thead', 'tfoot'],
            name: __('oro.cms.wysiwyg.component.table_row.label')
        },

        ...TableTypeDecorator
    },

    constructor: function TableRowTypeBuilder(...args) {
        TableRowTypeBuilder.__super__.constructor.apply(this, args);
    },

    isComponent(el) {
        return el.nodeType === el.ELEMENT_NODE && el.tagName === 'TR';
    }
}, {
    type: 'row'
});

export default TableRowType;
