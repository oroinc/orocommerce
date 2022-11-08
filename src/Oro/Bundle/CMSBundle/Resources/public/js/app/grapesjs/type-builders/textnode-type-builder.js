import BaseTypeBuilder from 'orocms/js/app/grapesjs/type-builders/base-type-builder';

const TextnodeTypeBuilder = BaseTypeBuilder.extend({
    parentType: 'textnode',

    constructor: function TextnodeTypeBuilder(...args) {
        TextnodeTypeBuilder.__super__.constructor.apply(this, args);
    },

    isComponent(el) {
        let result = '';

        if (el.nodeType === Node.TEXT_NODE) {
            result = {
                type: this.componentType,
                content: el.textContent
            };
        }

        // Checking if textnode placed on root without parent, wrap to text component to provide user editing
        if (result.type === this.componentType &&
            el.parentElement.tagName === 'BODY' &&
            el.textContent.replace(/\n/, '') !== ''
        ) {
            result = {
                type: 'text',
                tagName: 'div',
                components: [result]
            };
        }

        return result;
    }
});

export default TextnodeTypeBuilder;
