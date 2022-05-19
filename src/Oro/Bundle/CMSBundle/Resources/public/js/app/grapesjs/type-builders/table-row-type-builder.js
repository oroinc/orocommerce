import BaseTypeBuilder from 'orocms/js/app/grapesjs/type-builders/base-type-builder';

const TableRowTypeBuilder = BaseTypeBuilder.extend({
    parentType: 'row',

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
