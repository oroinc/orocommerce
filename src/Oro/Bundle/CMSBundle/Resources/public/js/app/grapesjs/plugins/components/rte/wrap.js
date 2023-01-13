import __ from 'orotranslation/js/translator';
import {
    foundClosestParentByTagName,
    saveCursor,
    unwrap,
    isTagUnderSelection,
    isBlockFormatted,
    isContainLists,
    isTag,
    formatting
} from './utils/utils';

export default {
    name: 'wrap',

    order: 20,

    group: 'text-style',

    icon: '<span class="fa fa-paint-brush" aria-hidden="true"></span>',

    attributes: {
        title: __('oro.cms.wysiwyg.wrap_action.title.default')
    },

    result(rte) {
        const cursor = saveCursor(rte);
        const selection = rte.selection();
        const range = selection.getRangeAt(0);
        const container = range.commonAncestorContainer;

        const unwrapSpan = container => {
            const node = foundClosestParentByTagName(container, 'span', true);
            unwrap(node);
        };

        if (isTagUnderSelection(rte, 'SPAN')) {
            if (selection.type === 'Caret') {
                unwrapSpan(container);
            } else if (selection.type === 'Range') {
                if (container.nodeType === Node.TEXT_NODE) {
                    unwrapSpan(container);
                } else {
                    const nodes = [...container.childNodes].filter(
                        node => range.intersectsNode(node) && node.tagName === 'SPAN'
                    );
                    nodes.forEach(node => unwrap(node));
                }
            }

            rte.el.normalize();
            cursor();
        } else {
            const {anchorNode, anchorOffset, extentNode, extentOffset} = selection;
            const closestFormatting = foundClosestParentByTagName(container, formatting);

            let nodes = [];

            if (container.childNodes.length) {
                nodes = [...container.childNodes].filter(node => range.intersectsNode(node));
            }

            if (closestFormatting &&
                container.nodeType === Node.TEXT_NODE &&
                closestFormatting.innerText === selection.toString()
            ) {
                nodes = [closestFormatting];
            }

            const insertedStr = nodes.length ? nodes.reduce((str, node, index) => {
                if (node.isSameNode(anchorNode) && anchorOffset > 0) {
                    str += index === 0 ? node.nodeValue.slice(anchorOffset) : node.nodeValue.slice(0, anchorOffset);
                    return str;
                }

                if (node.isSameNode(extentNode) && extentOffset > 0) {
                    str += index === 0 ? node.nodeValue.slice(extentOffset) : node.nodeValue.slice(0, extentOffset);
                    return str;
                }

                str += node.nodeType === Node.TEXT_NODE ? node.nodeValue : node.outerHTML;

                if (index === 0 || index === nodes.length - 1) {
                    node.remove();
                }
                return str;
            }, '') : selection;

            rte.insertHTML(
                `<span data-type="text-style" data-gjs-selectable="false">${insertedStr}</span>`, {select: true}
            );
        }
    },

    state(view, doc, rte) {
        const selection = rte.selection();

        if (selection.type === 'None') {
            return 0;
        }

        const range = selection.getRangeAt(0);
        const container = range.commonAncestorContainer;

        if (selection.type === 'Range') {
            const nodes = [...container.childNodes].filter(node => range.intersectsNode(node));
            if (nodes.some(node => isBlockFormatted(node) || isContainLists(node) || isTag(node, 'span'))) {
                return -1;
            }

            if (isTagUnderSelection(rte, 'SPAN')) {
                return 1;
            }

            return;
        }

        if (!isTagUnderSelection(rte, 'SPAN') &&
            (selection.type !== 'Range' || range.commonAncestorContainer.nodeType !== Node.TEXT_NODE)
        ) {
            return -1;
        }

        return selection && isTagUnderSelection(rte, 'SPAN') ? 1 : 0;
    },

    update({classes}, {$btn, attributes}) {
        if ($btn.hasClass(classes.active)) {
            $btn.attr({
                'title': __('oro.cms.wysiwyg.wrap_action.title.active'),
                'data-original-title': __('oro.cms.wysiwyg.wrap_action.title.active')
            });
        } else if ($btn.hasClass(classes.disabled)) {
            $btn.attr({
                'title': __('oro.cms.wysiwyg.wrap_action.title.disabled'),
                'data-original-title': __('oro.cms.wysiwyg.wrap_action.title.disabled')
            });
        } else {
            $btn.attr({
                'title': attributes.title,
                'data-original-title': attributes.title
            });
        }

        $btn.tooltip('update');
    }
};
