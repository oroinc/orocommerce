import __ from 'orotranslation/js/translator';
import $ from 'jquery';
import 'jquery.select2';
import selectTemplate from 'tpl-loader!orocms/templates/grapesjs-select-action.html';
import select2OptionTemplate from 'tpl-loader!orocms/templates/grapesjs-select2-option.html';
import {
    tags,
    formatting,
    findClosestFormattingBlock,
    findTextFormattingInRange,
    getNodeSiblings,
    isFormattedText,
    isContainLists,
    saveCursor,
    makeSurroundNode,
    findParentTag,
    isBlockFormatted,
    clearTextFormatting,
    isTag
} from './utils/utils';

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
        const cursor = saveCursor(rte);
        const value = rte.actionbar.querySelector('[name="tag"]').value;
        const selection = rte.selection();
        if (selection.type === 'None') {
            return;
        }

        const range = selection.getRangeAt(0);
        const surround = makeSurroundNode(rte.doc);
        const container = range.commonAncestorContainer;
        const containerIsTag = container.nodeType === Node.ELEMENT_NODE;
        const containerIsTextNode = container.nodeType === Node.TEXT_NODE;

        const removeParent = parentNode => {
            const parent = parentNode.parentNode;
            [...parentNode.childNodes].forEach(child => {
                parent.insertBefore(child, parentNode);
            });
            parentNode.remove();
            this.editor.trigger('change:canvasOffset');
        };

        const addParentOrReplace = (node, tagName) => {
            const oldParent = findClosestFormattingBlock(node);
            const newParent = rte.doc.createElement(tagName);
            node = findParentTag(container, ['span', ...formatting]);
            const isFormatted = node =>
                node.nodeType === Node.TEXT_NODE || isFormattedText(node) || isTag(node, 'span');

            const prevSiblings = getNodeSiblings(node, {
                callback: isFormatted,
                direction: 'previous'
            });

            const nextSiblings = getNodeSiblings(node, {
                callback: isFormatted
            });

            const toAppend = [...prevSiblings, node, ...nextSiblings];

            if (oldParent) {
                oldParent.after(newParent);
                newParent.append(...toAppend);
                oldParent.remove();
            } else {
                node.after(newParent);
                newParent.append(...toAppend);
            }
            this.editor.trigger('change:canvasOffset');
        };

        if (value === 'normal') {
            if (containerIsTag) {
                container.childNodes.forEach(node => {
                    if (range.intersectsNode(node)) {
                        clearTextFormatting(node);
                    }
                });

                if (isBlockFormatted(container)) {
                    removeParent(container);
                }

                this.editor.trigger('change:canvasOffset');
                cursor();
                return;
            }

            if (containerIsTextNode) {
                removeParent(findClosestFormattingBlock(container));
                cursor();
                return;
            }
        }

        if (!range.collapsed && containerIsTag && isContainLists(container)) {
            container.childNodes.forEach(node => {
                if (range.intersectsNode(node)) {
                    surround(node, value);
                }
            });

            selection.removeAllRanges();
            this.editor.trigger('change:canvasOffset');

            cursor();
            return;
        }

        if (containerIsTextNode &&
            !findClosestFormattingBlock(container) &&
            !isBlockFormatted(container.parentNode)
        ) {
            addParentOrReplace(container, value);
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
            if (range.commonAncestorContainer.nodeType === Node.ELEMENT_NODE) {
                const formatting = findTextFormattingInRange(range);
                if (formatting.length) {
                    $(select).select2('val', formatting[0]);
                    return;
                }
            }
        }

        if (value === '' && isBlockFormatted(rte.el)) {
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
