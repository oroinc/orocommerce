import BaseType from 'orocms/js/app/grapesjs/types/base-type';

const GridRowType = BaseType.extend({
    constructor: function GridRowTypeBuilder(options) {
        GridRowTypeBuilder.__super__.constructor.call(this, options);
    },

    editorEvents: {
        'selector:add': 'onSelectorAdd'
    },

    modelProps: {
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
        },

        init() {
            setTimeout(() => {
                this.components().each((component, index, collection) => {
                    const styles = component.getStyle();
                    if (!styles.width) {
                        component.setStyle({
                            width: (100 / collection.length).toFixed(2) + '%'
                        });
                    }
                });
            }, 0);
        }
    },

    onSelectorAdd(selector) {
        const privateCls = '.grid-row';
        privateCls.indexOf(selector.getFullName()) >= 0 && selector.set('private', 1);
    },

    isComponent(el) {
        return el.nodeType === el.ELEMENT_NODE && el.tagName === 'DIV' && el.classList.contains('grid-row');
    }
}, {
    type: 'grid-row'
});

export default GridRowType;
