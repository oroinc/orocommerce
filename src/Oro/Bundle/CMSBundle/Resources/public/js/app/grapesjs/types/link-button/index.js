import __ from 'orotranslation/js/translator';
import BaseType from 'orocms/js/app/grapesjs/types/base-type';
import LinkButtonTypeModel from './link-button-type-model';

const LinkButtonType = BaseType.extend({
    parentType: 'link',

    button: {
        label: __('oro.cms.wysiwyg.component.link_button.label'),
        category: 'Basic',
        attributes: {
            'class': 'fa fa-hand-pointer-o'
        },
        order: 35
    },

    TypeModel: LinkButtonTypeModel,

    constructor: function LinkButtonTypeBuilder(options) {
        LinkButtonTypeBuilder.__super__.constructor.call(this, options);
    },

    isComponent(el) {
        return el.nodeType === el.ELEMENT_NODE && el.tagName === 'A' && el.classList.contains('btn');
    }
}, {
    type: 'link-button',
    priority: 260
});

export default LinkButtonType;
