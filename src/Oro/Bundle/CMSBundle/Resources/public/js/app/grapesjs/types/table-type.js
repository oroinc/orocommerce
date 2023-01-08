import __ from 'orotranslation/js/translator';
import BaseType from 'orocms/js/app/grapesjs/types/base-type';
import TableEditView from '../controls/table-edit';
import TableTypeDecorator from '../controls/table-edit/table-type-decorator';

/**
 * Create table component type builder
 */
const TableType = BaseType.extend({
    /**
     * @inheritdoc
     */
    constructor: function TableTypeBuilder(options) {
        TableTypeBuilder.__super__.constructor.call(this, options);
    },

    commands: {
        'table-edit': {
            run(editor, sender, table) {
                this.tableEdit = new TableEditView({
                    container: this.editorModel.view.$el.find('#gjs-tools'),
                    table,
                    selected: editor.getSelected()
                });
            },

            stop() {
                this.tableEdit.dispose();
            }
        }
    },

    modelProps: {
        defaults: {
            tagName: 'table',
            draggable: false,
            removable: false,
            copyable: false,
            classes: ['table'],
            name: __('oro.cms.wysiwyg.component.table.label')
        },

        ...TableTypeDecorator,

        init() {
            const components = this.get('components');

            if (!components.length) {
                components.add([{
                    type: 'thead'
                }, {
                    type: 'tbody'
                }]);
            }

            this.bindModelEvents();
        }
    },

    isComponent(el) {
        return el.nodeType === el.ELEMENT_NODE && el.tagName === 'TABLE';
    }
}, {
    type: 'table'
});

export default TableType;
