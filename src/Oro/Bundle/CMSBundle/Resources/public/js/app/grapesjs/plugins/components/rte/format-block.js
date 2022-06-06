import __ from 'orotranslation/js/translator';
import $ from 'jquery';
import 'jquery.select2';
import selectTemplate from 'tpl-loader!orocms/templates/grapesjs-select-action.html';
import select2OptionTemplate from 'tpl-loader!orocms/templates/grapesjs-select2-option.html';
import * as utils from './utils/utils';

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
        const cursor = utils.saveCursor(rte);
        const value = rte.actionbar.querySelector('[name="tag"]').value;
        const selection = rte.selection();

        if (selection.type === 'None') {
            return;
        }

        const range = selection.getRangeAt(0);
        const surround = utils.makeSurroundNode(rte.doc);
        const isTag = range.commonAncestorContainer.nodeType === 1;
        const isTextNode = range.commonAncestorContainer.nodeType === 3;

        const removeParent = parentNode => {
            const parent = parentNode.parentNode;
            [...parentNode.childNodes].forEach(child => {
                parent.insertBefore(child, parentNode);
            });
            parentNode.remove();
            this.editor.trigger('change:canvasOffset');
        };

        if (value === 'normal') {
            if (isTag) {
                range.commonAncestorContainer.childNodes.forEach(node => {
                    if (range.intersectsNode(node)) {
                        utils.clearTextFormatting(node);
                    }
                });

                if (utils.isBlockFormatted(range.commonAncestorContainer)) {
                    removeParent(range.commonAncestorContainer);
                }

                this.editor.trigger('change:canvasOffset');
                cursor();
                return;
            }

            if (isTextNode) {
                removeParent(utils.findClosestFormattingBlock(range.commonAncestorContainer));
                cursor();
                return;
            }
        }

        if (!range.collapsed && isTag && utils.isContainLists(range.commonAncestorContainer)) {
            range.commonAncestorContainer.childNodes.forEach(node => {
                if (range.intersectsNode(node)) {
                    surround(node, value);
                }
            });

            selection.removeAllRanges();
            this.editor.trigger('change:canvasOffset');

            cursor();
            return;
        }

        if (isTextNode &&
            !utils.isBlockFormatted(range.commonAncestorContainer.parentNode) &&
            !utils.isFormattedText(range.commonAncestorContainer.parentNode)
        ) {
            const newParent = rte.doc.createElement(value);
            const docFragment = rte.doc.createDocumentFragment();
            newParent.appendChild(utils.findParentTag(range.commonAncestorContainer, 'SPAN'));
            docFragment.appendChild(newParent);
            range.deleteContents();
            range.insertNode(docFragment);
            range.setStartAfter(newParent);
            range.collapse(true);
            selection.removeAllRanges();
            selection.addRange(range);
            this.editor.trigger('change:canvasOffset');

            cursor();
            return;
        }

        this.editor.trigger('change:canvasOffset');
        cursor();
        return rte.exec('formatBlock', value);
    },

    update(rte, action) {
        const value = rte.doc.queryCommandValue(action.name);
        const select = rte.actionbar.querySelector('[name="tag"]');
        const selection = rte.doc.getSelection();
        if (selection.anchorNode) {
            const range = selection.getRangeAt(0);
            if (range.commonAncestorContainer.nodeType === 1) {
                const formatting = utils.findTextFormattingInRange(range);
                if (formatting.length) {
                    $(select).select2('val', formatting[0]);
                    return;
                }
            }
        }

        if (value === '' && utils.isBlockFormatted(rte.el)) {
            $(select).select2('val', rte.el.tagName.toLowerCase());
            return;
        }

        if (value !== 'false') {
            if (utils.tags.includes(value)) {
                $(select).select2('val', value);
            } else {
                $(select).select2('val', 'normal');
            }
        }
    }
};
