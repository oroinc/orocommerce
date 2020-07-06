import BaseTypeBuilder from 'orocms/js/app/grapesjs/type-builders/base-type-builder';

const ColumnTypeBuilder = BaseTypeBuilder.extend({
    constructor: function ColumnTypeBuilder(options) {
        ColumnTypeBuilder.__super__.constructor.call(this, options);
    },

    editorEvents: {
        'selector:add': 'onSelectorAdd'
    },

    modelMixin: {
        defaults: {
            classes: ['grid-cell'],
            draggable: '.grid-row',
            resizable: {
                tl: 0,
                tc: 0,
                tr: 0,
                bl: 0,
                br: 0,
                bc: 0,
                minDim: 25,
                maxDim: 75,
                step: 0.2,
                currentUnit: 0,
                unitWidth: '%'
            }
        }
    },

    onSelectorAdd(selector) {
        const privateCls = '.grid-cell';
        privateCls.indexOf(selector.getFullName()) >= 0 && selector.set('private', 1);
    },

    isComponent(el) {
        let result = null;

        if (el.tagName === 'DIV' && el.classList.contains('grid-cell')) {
            result = {
                type: this.componentType
            };
        }

        return result;
    }
});

export default ColumnTypeBuilder;
