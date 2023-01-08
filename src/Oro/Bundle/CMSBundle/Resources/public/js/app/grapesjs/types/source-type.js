import BaseType from 'orocms/js/app/grapesjs/types/base-type';

const SourceType = BaseType.extend({
    TypeModel(BaseTypeModel, props) {
        const SourceTypeModel = BaseTypeModel.extend({
            ...props,

            constructor: function SourceTypeModel(...args) {
                return SourceTypeModel.__super__.constructor.apply(this, args);
            },

            getAttributes(opts = {}) {
                const attr = SourceTypeModel.__super__.getAttributes.call(this, opts);

                for (const [key, value] of Object.entries(attr)) {
                    if (!value) {
                        delete attr[key];
                    }
                }

                return attr;
            }
        });

        Object.defineProperty(SourceTypeModel.prototype, 'defaults', {
            value: {
                ...SourceTypeModel.prototype.defaults,
                tagName: 'source',
                attributes: {
                    srcset: '',
                    type: '',
                    media: '',
                    sizes: ''
                }
            }
        });

        return SourceTypeModel;
    },

    constructor: function SourceTypeBuilder(options) {
        SourceTypeBuilder.__super__.constructor.call(this, options);
    },

    isComponent(el) {
        return el.nodeType === Node.ELEMENT_NODE && el.tagName.toLowerCase() === 'source';
    }
}, {
    type: 'source'
});

export default SourceType;
