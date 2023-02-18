import BaseTypeBuilder from 'orocms/js/app/grapesjs/type-builders/base-type-builder';

const FigureTypeBuilder = BaseTypeBuilder.extend({
    modelMixin: {
        defaults: {
            name: 'Figure',
            tagName: 'figure'
        }
    },

    constructor: function FigureTypeBuilder(...args) {
        FigureTypeBuilder.__super__.constructor.apply(this, args);
    },

    isComponent(el) {
        return el.nodeType === Node.ELEMENT_NODE && el.tagName.toLowerCase() === 'figure';
    }
});

export default FigureTypeBuilder;
