import BaseTypeBuilder from 'orocms/js/app/grapesjs/type-builders/base-type-builder';

export const escape = (str = '') => {
    return `${str}`
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;')
        .replace(/`/g, '&#96;');
};

const TextNodeTypeBuilder = BaseTypeBuilder.extend({
    parentType: 'textnode',

    constructor: function TextNodeTypeBuilder(...args) {
        return TextNodeTypeBuilder.__super__.constructor.apply(this, args);
    },

    modelMixin: {
        toHTML() {
            const parent = this.parent();
            const cnt = this.get('content');
            return parent && parent.is('script') ? cnt : escape(cnt);
        }
    }
});

export default TextNodeTypeBuilder;
