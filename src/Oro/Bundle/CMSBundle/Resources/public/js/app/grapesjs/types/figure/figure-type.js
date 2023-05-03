import BaseType from 'orocms/js/app/grapesjs/types/base-type';

const FigureType = BaseType.extend({
    modelProps: {
        defaults: {
            name: 'Figure',
            tagName: 'figure'
        }
    },

    constructor: function FigureType(...args) {
        FigureType.__super__.constructor.apply(this, args);
    },

    isComponent(el) {
        return el.nodeType === Node.ELEMENT_NODE && el.tagName.toLowerCase() === 'figure';
    }
}, {
    type: 'figure'
});

export default FigureType;
