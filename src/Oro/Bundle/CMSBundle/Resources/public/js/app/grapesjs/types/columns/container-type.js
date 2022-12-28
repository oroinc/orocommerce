import __ from 'orotranslation/js/translator';
import BaseType from 'orocms/js/app/grapesjs/types/base-type';

const ContainerType = BaseType.extend({
    button: {
        label: __('oro.cms.wysiwyg.component.container.label'),
        category: {
            label: __('oro.cms.wysiwyg.block_manager.categories.layout'),
            order: 1
        },
        attributes: {
            'class': 'fa fa-square-o'
        }
    },

    modelProps: {
        defaults: {
            name: __('oro.cms.wysiwyg.component.container.label'),
            tagName: 'div',
            classes: ['block'],
            changeTypes: {
                columns: 'columns-item',
                tiles: 'tiles-item'
            }
        },

        changeTypeByParent() {
            const parent = this.parent();

            if (!parent) {
                return;
            }

            const type = this.get('changeTypes')[parent.get('type')];

            type && setTimeout(() => this.replaceWith({
                type,
                components: this.getInnerHTML()
            }));
        }
    },

    viewProps: {
        onRender() {
            this.$el.css('min-height', 50);
        }
    },

    editorEvents: {
        'component:mount': 'onMount'
    },

    constructor: function ContainerTypeBuilder(...args) {
        ContainerTypeBuilder.__super__.constructor.apply(this, args);
    },

    onMount(model) {
        if (model.is(this.componentType)) {
            model.changeTypeByParent();
        }
    },

    isComponent(el) {
        return el.nodeType === Node.ELEMENT_NODE && el.tagName === 'DIV' && el.classList.contains('block');
    }
}, {
    type: 'div-block'
});

export default ContainerType;
