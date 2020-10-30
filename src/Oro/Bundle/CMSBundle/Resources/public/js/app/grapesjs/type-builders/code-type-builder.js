import _ from 'underscore';
import __ from 'orotranslation/js/translator';
import BaseTypeBuilder from 'orocms/js/app/grapesjs/type-builders/base-type-builder';

const CodeTypeBuilder = BaseTypeBuilder.extend({
    parentType: 'text',

    button: {
        label: __('oro.cms.wysiwyg.component.code.label'),
        category: 'Basic',
        attributes: {
            'class': 'fa fa-code'
        }
    },

    modelMixin: {
        initialize(...args) {
            this.constructor.__super__.initialize.call(this, ...args);
            /**
             * The model data changes along with the content editing, in which case it needs to be repeated
             * 'unescape' and 'escape' content data.
             *
             * As an example, drag a component to any location, this will provoke the re-render component
             * content from escaped model data.
             */
            this.attributes.content = _.escape(_.unescape(this.attributes.content));
        }
    },

    viewMixin: {
        events: {
            dblclick: 'onDoubleClick'
        },

        onDoubleClick(e) {
            this.em.get('Commands').run('gjs-open-code-page');
            e.stopPropagation();
        }
    },

    template: _.template(`<pre>${__('oro.cms.wysiwyg.component.code.placeholder')}</pre>`),

    constructor: function CodeTypeBuilder(options) {
        CodeTypeBuilder.__super__.constructor.call(this, options);
    },

    isComponent(el) {
        let result = null;

        if (el.tagName === 'PRE') {
            result = {
                type: this.componentType
            };
        }

        return result;
    }
});

export default CodeTypeBuilder;
