import _ from 'underscore';
import BaseTypeBuilder from 'orocms/js/app/grapesjs/type-builders/base-type-builder';

const DefaultTypeBuilder = BaseTypeBuilder.extend({
    viewMixin: {
        updateAttributes() {
            const attrs = [];
            const {model, $el, el, config} = this;
            const {highlightable, textable, type} = model.attributes;
            const {draggableComponents} = config;

            let defaultAttr = {'data-gjs-type': type || 'default'};

            if (draggableComponents) {
                defaultAttr.draggable = true;
            }

            if (highlightable) {
                defaultAttr['data-highlightable'] = 1;
            }

            if (textable) {
                defaultAttr = {
                    ...defaultAttr,
                    'contenteditable': 'false',
                    'data-gjs-textable': 'true'
                };
            }

            // Remove all current attributes
            _.each(el.attributes, attr => attrs.push(attr.nodeName));
            attrs.forEach(attr => $el.removeAttr(attr));
            const attr = {
                ...defaultAttr,
                ...model.getAttributes()
            };

            // Remove all `false` attributes
            Object.keys(attr).forEach(key => attr[key] === false && delete attr[key]);
            $el.attr(attr);

            this.updateStyle();
        }
    },

    isComponent(el) {
        let result = null;

        if (el.tagName === 'DIV') {
            result = {
                type: this.componentType
            };
        }

        return result;
    }
});

export default DefaultTypeBuilder;
