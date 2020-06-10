import __ from 'orotranslation/js/translator';
import selectTemplate from 'tpl-loader!orocms/templates/grapesjs-select-action.html';

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

        if (value !== 'false') {
            if (['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p'].indexOf(value) !== -1) {
                select.value = value;
            } else {
                select.value = 'normal';
            }
        }
    }
};
