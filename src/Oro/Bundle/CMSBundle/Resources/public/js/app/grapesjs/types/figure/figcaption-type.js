import BaseType from 'orocms/js/app/grapesjs/types/base-type';

const FigcaptionType = BaseType.extend({
    modelProps: {
        defaults: {
            name: 'Figure Caption',
            tagName: 'figcaption'
        }
    },

    constructor: function FigcaptionType(...args) {
        FigcaptionType.__super__.constructor.apply(this, args);
    },

    isComponent(el) {
        return el.nodeType === Node.ELEMENT_NODE && el.tagName.toLowerCase() === 'figcaption';
    }
}, {
    type: 'figcaption'
});

export default FigcaptionType;
