import __ from 'orotranslation/js/translator';
import BaseType from 'orocms/js/app/grapesjs/types/base-type';
import TypeModel from './text-type-model';
import TypeView from './text-type-view';
import {TAGS} from './tags';

const TextType = BaseType.extend({
    parentType: 'text',

    TypeModel,

    TypeView,

    button: {
        label: __('oro.cms.wysiwyg.component.text.label'),
        category: 'Basic',
        attributes: {
            'class': 'gjs-fonts gjs-f-text'
        },
        order: 5
    },

    constructor: function TextType(options) {
        TextType.__super__.constructor.call(this, options);
    },

    isComponent(el) {
        let _res = {
            tagName: el.tagName.toLowerCase()
        };

        if (TAGS.includes(_res.tagName)) {
            _res = {
                ..._res,
                type: 'text'
            };
        }

        return _res;
    }
}, {
    type: 'text'
});

export default TextType;
