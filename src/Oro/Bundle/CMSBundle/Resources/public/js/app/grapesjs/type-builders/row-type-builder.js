import BaseTypeBuilder from 'orocms/js/app/grapesjs/type-builders/base-type-builder';

const RowTypeBuilder = BaseTypeBuilder.extend({
    constructor: function RowTypeBuilder(options) {
        RowTypeBuilder.__super__.constructor.call(this, options);
    },

    editorEvents: {
        'selector:add': 'onSelectorAdd'
    },

    modelMixin: {
        defaults: {
            classes: ['grid-row'],
            droppable: '.grid-cell',
            resizable: {
                tl: 0,
                tc: 0,
                tr: 0,
                cl: 0,
                cr: 0,
                bl: 0,
                br: 0,
                minDim: 50
            }
        }
    },

    onSelectorAdd(selector) {
        const privateCls = '.grid-row';
        privateCls.indexOf(selector.getFullName()) >= 0 && selector.set('private', 1);
    },

    isComponent(el) {
        let result = null;

        if (el.tagName === 'DIV' && el.classList.contains('grid-row')) {
            result = {
                type: this.componentType
            };
        }

        return result;
    }
});

export default RowTypeBuilder;
