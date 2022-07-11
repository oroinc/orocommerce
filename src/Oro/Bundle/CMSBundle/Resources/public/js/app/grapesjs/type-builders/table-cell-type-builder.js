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
                    content: this.get('tagName') === 'th' ? 'Header cell' : 'Body cell'
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
        let result = null;

        if (el.tagName === 'TD' || el.tagName === 'TH') {
            result = {
                type: this.componentType
            };
        }

        return result;
    }
});

export default TableCellTypeBuilder;
