import BaseType from 'orocms/js/app/grapesjs/types/base-type';
import tableResponsiveTemplate from 'tpl-loader!orocms/templates/grapesjs-table-responsive.html';
import __ from 'orotranslation/js/translator';
import TableTypeDecorator from '../controls/table-edit/table-type-decorator';

/**
 * Create responsive table component type for builder
 */
const TableResponsiveType = BaseType.extend({
    button: {
        label: __('oro.cms.wysiwyg.component.table.label'),
        category: 'Basic',
        order: 70,
        attributes: {
            'class': 'fa fa-table'
        }
    },

    template: tableResponsiveTemplate,

    /**
     * @inheritdoc
     */
    constructor: function TableResponsiveTypeBuilder(options) {
        TableResponsiveTypeBuilder.__super__.constructor.call(this, options);
    },

    modelProps: {
        defaults: {
            tagName: 'div',
            droppable: ['table'],
            classes: ['table-responsive'],
            name: __('oro.cms.wysiwyg.component.table_responsive.label')
        },

        ...TableTypeDecorator,

        init() {
            const components = this.get('components');
            if (!components.length) {
                components.add({
                    type: 'table'
                });
            }

            this.bindModelEvents();

            const table = this.findType('table');
            if (table.length) {
                table[0].referrer = this;
            }
        },

        getTable() {
            const table = this.findType('table');

            if (table.length) {
                return table[0];
            }
        }
    },

    isComponent(el) {
        return el.nodeType === Node.ELEMENT_NODE &&
            el.tagName === 'DIV' &&
            el.classList.contains(this.componentType);
    }
}, {
    type: 'table-responsive'
});

export default TableResponsiveType;
