import __ from 'orotranslation/js/translator';
import {findClosestFormattingBlock, findClosestListType, getOffsetProp, getParentsUntil} from './utils/utils';

const decreaseOffset = rte => {
    const selection = rte.selection();
    const range = selection.getRangeAt(0);
    const container = range.commonAncestorContainer;

    const listType = findClosestListType(container);
    if (listType) {
        return rte.execute(`${listType}:sublist:remove`);
    }

    const block = findClosestFormattingBlock(container);
    if (!block) {
        return;
    }

    const offsetProp = getOffsetProp();
    const offset = parseInt(block.style[offsetProp] || 0) - 40;

    if (offset > 0) {
        block.style[offsetProp] = offset + 'px';
    } else {
        block.style.removeProperty(offsetProp);
    }
};

export default {
    name: 'outdent',

    order: 30,

    group: 'indent-level',

    icon: '<span class="fa fa-outdent" aria-hidden="true"></span>',

    attributes: {
        title: __('oro.cms.wysiwyg.simple_actions.outdent.title')
    },

    result(rte) {
        decreaseOffset(rte);
    },

    state(view, doc, rte) {
        const selection = rte.selection();
        if (selection.type === 'None') {
            return 0;
        }

        const range = selection.getRangeAt(0);
        const container = range.commonAncestorContainer;
        const block = findClosestFormattingBlock(container);

        const listType = findClosestListType(container);

        if (listType) {
            const depth = getParentsUntil(container, rte.el)
                .filter(tag => tag.tagName === (listType === 'ordered' ? 'OL' : 'UL')).length;

            return depth > 1 ? 0 : -1;
        }

        if (block && block.style[getOffsetProp()]) {
            return 0;
        } else {
            return -1;
        }
    }
};
