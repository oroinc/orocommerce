import _ from 'underscore';
import __ from 'orotranslation/js/translator';
import BaseType from 'orocms/js/app/grapesjs/types/base-type';

const CodeType = BaseType.extend({
    button: {
        label: __('oro.cms.wysiwyg.component.code.label'),
        category: 'Basic',
        attributes: {
            'class': 'fa fa-file-code-o'
        },
        order: 75
    },

    modelProps: {
        defaults: {
            tagName: 'pre'
        },

        init() {
            const components = this.get('components');

            if (!components.length) {
                components.add({
                    tagName: 'code'
                });
            }

            this.propagateChildProps();
        },

        getContent() {
            const contentComp = this.findType('textnode')[0];
            return contentComp ? contentComp.get('content') : '';
        },

        setContent(value) {
            this.components(`<code>${value}</code>`);
            this.propagateChildProps();
        },

        propagateChildProps() {
            const components = this.get('components');
            components.forEach(component => component.set({
                layerable: 0,
                selectable: 0,
                hoverable: 0,
                editable: 0,
                draggable: 0,
                droppable: 0,
                highlightable: 0
            }));
        }
    },

    viewProps: {
        events: {
            dblclick: 'onDoubleClick'
        },

        onDoubleClick(e) {
            this.em.get('Commands').run('gjs-open-code-page');
            e.stopPropagation();
        }
    },

    template: _.template(`<pre><code>${__('oro.cms.wysiwyg.component.code.placeholder')}</code></pre>`),

    constructor: function CodeTypeBuilder(options) {
        CodeTypeBuilder.__super__.constructor.call(this, options);
    },

    isComponent(el) {
        return (el.nodeType === Node.ELEMENT_NODE && el.tagName === 'PRE') &&
            (el.firstChild.nodeType === Node.ELEMENT_NODE && el.firstChild.tagName === 'CODE');
    }
}, {
    type: 'code'
});

export default CodeType;
