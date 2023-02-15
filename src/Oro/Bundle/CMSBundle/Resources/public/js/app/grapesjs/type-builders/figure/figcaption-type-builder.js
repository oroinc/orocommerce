import BaseTypeBuilder from 'orocms/js/app/grapesjs/type-builders/base-type-builder';

const FigcaptionTypeBuilder = BaseTypeBuilder.extend({
    modelProps: {
        defaults: {
            name: 'Figure Caption',
            tagName: 'figcaption'
        }
    },

    constructor: function FigcaptionTypeBuilder(...args) {
        FigcaptionTypeBuilder.__super__.constructor.apply(this, args);
    },

    isComponent(el) {
        return el.nodeType === Node.ELEMENT_NODE && el.tagName.toLowerCase() === 'figcaption';
    }
});

export default FigcaptionTypeBuilder;
