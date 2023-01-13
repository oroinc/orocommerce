import __ from 'orotranslation/js/translator';
import BaseType from 'orocms/js/app/grapesjs/types/base-type';

const TextWrapperType = BaseType.extend({
    modelProps: {
        defaults: {
            droppable: false,
            draggable: '[data-gjs-type="text"]',
            textable: true,
            name: __('oro.cms.wysiwyg.component.text_wrapper.label')
        }
    },

    editorEvents: {
        'rte:enable': 'onRteEnable',
        'rte:disable': 'onRteDisable'
    },

    viewProps: {
        events: {
            dblclick: 'onActive'
        },

        onActive() {
            const {model, em} = this;
            const parentText = model.closest('div[contenteditable="true"]');

            if (parentText) {
                em.get('Editor').select(parentText);
                setTimeout(() => em.get('Editor').trigger('change:canvasOffset'));
            }
        },

        onRender() {
            this.$el.removeAttr('contenteditable');
        }
    },

    constructor: function TextWrapperTypeBuilder(...args) {
        TextWrapperTypeBuilder.__super__.constructor.apply(this, args);
    },

    onRteEnable({model} = {}, {editableEl}) {
        if (!model) {
            return;
        }

        editableEl.querySelectorAll('span[contenteditable="false"]').forEach(
            el => el.removeAttribute('contenteditable')
        );

        model.em.get('Editor').select(model);
        const components = model.findType(this.componentType);
        if (components) {
            components.forEach(component => component.set({
                selectable: false,
                draggable: false
            }));
        }
        setTimeout(() => model.em.get('Editor').trigger('change:canvasOffset'));
    },

    onRteDisable({model} = {}) {
        if (!model) {
            return;
        }

        const components = model.findType(this.componentType);
        if (components) {
            components.forEach(component => component.set({
                selectable: true,
                draggable: true
            }));
        }
    },

    isComponent(el) {
        let result = null;

        if (el.tagName === 'SPAN' && el.getAttribute('data-type') === 'text-style') {
            result = {
                type: this.componentType,
                textComponent: true
            };
        }

        return result;
    }
}, {
    type: 'text-style'
});

export default TextWrapperType;
