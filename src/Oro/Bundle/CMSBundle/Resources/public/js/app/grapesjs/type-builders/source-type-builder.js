
import BaseTypeBuilder from 'orocms/js/app/grapesjs/type-builders/base-type-builder';

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

            if (attrs['data-srcset-exp']) {
                $el.attr('srcset', attrs['data-srcset-exp']);
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
