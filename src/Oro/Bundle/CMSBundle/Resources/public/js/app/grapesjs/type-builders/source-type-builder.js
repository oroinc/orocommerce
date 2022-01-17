import BaseTypeBuilder from 'orocms/js/app/grapesjs/type-builders/base-type-builder';

const SourceTypeBuilder = BaseTypeBuilder.extend({
    modelMixin: {
        defaults: {
            tagName: 'source',
            attributes: {
                srcset: '',
                type: '',
                media: '',
                sizes: ''
            }
        },

        getAttributes(opts = {}) {
            const attr = this.constructor.__super__.getAttributes.call(this, opts);

            for (const [key, value] of Object.entries(attr)) {
                if (!value) {
                    delete attr[key];
                }
            }

            return attr;
        }
    },

    constructor: function SourceTypeBuilder(options) {
        SourceTypeBuilder.__super__.constructor.call(this, options);
    },

    isComponent(el) {
        if (el.nodeType === 1 && el.tagName.toLowerCase() === 'source') {
            return {
                type: this.componentType
            };
        }
    }
});

export default SourceTypeBuilder;
