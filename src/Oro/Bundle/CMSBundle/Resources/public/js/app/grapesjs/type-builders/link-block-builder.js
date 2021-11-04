import _ from 'underscore';
import __ from 'orotranslation/js/translator';
import BaseTypeBuilder from 'orocms/js/app/grapesjs/type-builders/base-type-builder';

const LinkButtonTypeBuilder = BaseTypeBuilder.extend({
    parentType: 'link',

    constructor: function LinkButtonTypeBuilder(options) {
        LinkButtonTypeBuilder.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize(options) {
        _.extend(this, _.pick(options, 'editor', 'componentType'));
    },

    execute() {
        this.editor.DomComponents.addType(this.componentType, {
            isComponent: el => {
                let result = null;

                if (el.tagName === 'A' && el.classList.contains('link-block')) {
                    result = {
                        type: this.componentType
                    };
                }

                return result;
            },
            extend: this.parentType,
            model: {
                defaults: {
                    classes: ['link-block'],
                    traits: ['href', 'title', 'target']
                }
            },
            extendView: this.parentType,
            view: {
                events: {
                    dblclick: 'onActive'
                }
            }
        });

        this.editor.BlockManager.get(this.componentType).set({
            category: 'Basic',
            label: __('oro.cms.wysiwyg.component.link_block.label'),
            attributes: {
                'class': 'fa fa-link'
            },
            content: {
                type: this.componentType,
                editable: false,
                droppable: true,
                style: {
                    'display': 'inline-block',
                    'padding': '5px',
                    'min-height': '50px',
                    'min-width': '50px'
                }
            }
        });
    }
});

export default LinkButtonTypeBuilder;
