import __ from 'orotranslation/js/translator';
import BaseTypeBuilder from 'orocms/js/app/grapesjs/type-builders/base-type-builder';
import TableTypeDecorator from '../controls/table-edit/table-type-decorator';

const TableCellTypeBuilder = BaseTypeBuilder.extend({
    parentType: 'cell',

    modelMixin: {
        defaults: {
            removable: false,
            copyable: false,
            draggable: false
        },

        ...TableTypeDecorator,

        init() {
            const components = this.get('components');
            if (!components.length) {
                components.add({
                    type: 'text',
                    components: [{
                        type: 'textnode',
                        content: this.get('tagName') === 'th'
                            ? __('oro.cms.wysiwyg.component.table.header_cell_label')
                            : __('oro.cms.wysiwyg.component.table.body_cell_label')
                    }]
                });
            }

            this.set('toolbar', []);

            this.bindModelEvents();
        }
    },

    constructor: function TableCellTypeBuilder(...args) {
        TableCellTypeBuilder.__super__.constructor.apply(this, args);
    },

    isComponent(el) {
        return el.nodeType === el.ELEMENT_NODE && (el.tagName === 'TD' || el.tagName === 'TH');
    }
});

export default TableCellTypeBuilder;
