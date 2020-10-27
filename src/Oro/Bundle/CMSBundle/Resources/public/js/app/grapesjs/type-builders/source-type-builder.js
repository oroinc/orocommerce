
import BaseTypeBuilder from 'orocms/js/app/grapesjs/type-builders/base-type-builder';
import DigitalAssetHelper from 'orocms/js/app/grapesjs/helpers/digital-asset-helper';

const SourceTypeBuilder = BaseTypeBuilder.extend({
    modelMixin: {
        defaults: {
            tagName: 'source'
        },

        getAttrToHTML() {
            const attrs = this.constructor.__super__.getAttrToHTML.call(this);
            attrs['srcset'] = attrs['data-srcset-exp'];
            delete attrs['data-srcset-exp'];
            return attrs;
        }
    },

    viewMixin: {
        postRender() {
            const {$el, model} = this;

            const attrs = model.get('attributes');
            const imageSrc = DigitalAssetHelper.getImageUrlFromTwigTag(attrs['data-srcset-exp']);

            if (imageSrc) {
                $el.attr('srcset', imageSrc);
            } else {
                $el.attr('srcset', '');
            }
        }
    },

    isComponent(el) {
        let result = null;

        if (el.tagName === 'SOURCE') {
            result = {
                type: this.componentType
            };
        }

        return result;
    }
});

export default SourceTypeBuilder;
