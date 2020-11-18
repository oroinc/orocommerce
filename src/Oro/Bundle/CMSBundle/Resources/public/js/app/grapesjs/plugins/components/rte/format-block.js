import __ from 'orotranslation/js/translator';
import $ from 'jquery';
import 'jquery.select2';
import selectTemplate from 'tpl-loader!orocms/templates/grapesjs-select-action.html';
import select2OptionTemplate from 'tpl-loader!orocms/templates/grapesjs-select2-option.html';

const tags = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p'];

export default {
    name: 'formatBlock',

    icon: selectTemplate({
        options: {
            normal: __('oro.cms.wysiwyg.format_block.normal'),
            p: __('oro.cms.wysiwyg.format_block.p'),
            h1: __('oro.cms.wysiwyg.format_block.h1'),
            h2: __('oro.cms.wysiwyg.format_block.h2'),
            h3: __('oro.cms.wysiwyg.format_block.h3'),
            h4: __('oro.cms.wysiwyg.format_block.h4'),
            h5: __('oro.cms.wysiwyg.format_block.h5'),
            h6: __('oro.cms.wysiwyg.format_block.h6')
        },
        name: 'tag'
    }),

    event: 'change',

    attributes: {
        'title': __('oro.cms.wysiwyg.format_block.title'),
        'class': 'gjs-rte-action text-format-action'
    },

    order: 10,

    group: 'format-block',

    init(rte) {
        const $select = $(rte.actionbar.querySelector('[name="tag"]'));
        $select.inputWidget('create', 'select2', {
            initializeOptions: {
                minimumResultsForSearch: -1,
                dropdownCssClass: 'gjs-rte-select2-dropdown',
                formatResult: state => select2OptionTemplate({state})
            }
        });
    },

    result(rte) {
        const value = rte.actionbar.querySelector('[name="tag"]').value;

        if (value === 'normal') {
            const parentNode = rte.selection().getRangeAt(0).startContainer.parentNode;
            const text = parentNode.innerText;
            parentNode.remove();

            return rte.insertHTML(text);
        }
        return rte.exec('formatBlock', value);
    },

    update(rte, action) {
        const value = rte.doc.queryCommandValue(action.name);
        const select = rte.actionbar.querySelector('[name="tag"]');

        if (value === '' && tags.includes(rte.el.tagName.toLowerCase())) {
            $(select).select2('val', rte.el.tagName.toLowerCase());
            return;
        }

        if (value !== 'false') {
            if (tags.includes(value)) {
                $(select).select2('val', value);
            } else {
                $(select).select2('val', 'normal');
            }
        }
    }
};
