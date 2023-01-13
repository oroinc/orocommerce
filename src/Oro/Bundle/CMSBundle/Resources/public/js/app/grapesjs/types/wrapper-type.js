import BaseType from 'orocms/js/app/grapesjs/types/base-type';

const WrapperType = BaseType.extend({
    editorEvents: {
        'style:property:update': 'onStyleUpdate'
    },

    modelProps: {
        defaults: {
            tagName: 'div',
            removable: false,
            copyable: false,
            draggable: false,
            classes: 'cms-wrapper',
            components: [],
            traits: [],
            stylable: [
                'background',
                'background-color',
                'background-image',
                'background-repeat',
                'background-attachment',
                'background-position',
                'background-size'
            ]
        },

        init() {
            this.addClass('cms-wrapper');
        },

        toHTML(opts) {
            return this.getInnerHTML(opts);
        }
    },

    onStyleUpdate() {
        const {SelectorManager} = this.editor;
        const target = SelectorManager.selectorTags.getTarget();
        if (target && target.is(this.componentType)) {
            setTimeout(() => SelectorManager.selectorTags.syncStyle(), 0);
        }
    },

    constructor: function WrapperTypeBuilder(...args) {
        return WrapperTypeBuilder.__super__.constructor.apply(this, args);
    },

    isComponent() {
        return false;
    }
}, {
    type: 'wrapper'
});

export default WrapperType;
