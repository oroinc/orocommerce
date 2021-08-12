import BaseTypeBuilder from 'orocms/js/app/grapesjs/type-builders/base-type-builder';

/**
 * Create table component type builder
 */
const TableTypeBuilder = BaseTypeBuilder.extend({
    /**
     * @inheritdoc
     */
    constructor: function TableTypeBuilder(options) {
        TableTypeBuilder.__super__.constructor.call(this, options);
    },

    modelMixin: {
        defaults: {
            tagName: 'table',
            draggable: ['div'],
            droppable: ['tbody', 'thead', 'tfoot'],
            classes: ['table']
        },

        initialize(...args) {
            this.constructor.__super__.initialize.apply(this, args);

            const components = this.get('components');

            if (!components.length) {
                components.add({
                    type: 'thead'
                });
                components.add({
                    type: 'tbody'
                });
                components.add({
                    type: 'tfoot'
                });
            }
        }
    },

    isComponent(el) {
        let result = null;

        if (el.tagName === 'TABLE') {
            result = {
                type: this.componentType
            };
        }

        return result;
    }
});

export default TableTypeBuilder;
