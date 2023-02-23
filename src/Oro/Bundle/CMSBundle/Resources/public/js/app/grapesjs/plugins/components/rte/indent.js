import __ from 'orotranslation/js/translator';
import {
    findClosestFormattingBlock,
    findClosestListType,
    saveCursor,
    getOffsetProp,
    getParentsUntil,
    findParentTag,
    formatting
} from './utils/utils';
import ListMixin from './utils/list-mixins';

const increaseOffset = rte => {
    const cursor = saveCursor(rte);
    const selection = rte.selection();
    const range = selection.getRangeAt(0);
    const container = range.commonAncestorContainer;

    const listType = findClosestListType(container);
    if (listType) {
        return rte.execute(`${listType}:sublist:add`);
    }

    let block = findClosestFormattingBlock(container);

    if (!block) {
        block = rte.doc.createElement('P');
        const containerToFormat = findParentTag(container, formatting);
        containerToFormat.parentNode.insertBefore(block, containerToFormat);
        block.append(containerToFormat);
        cursor();
    }

    const offsetProp = getOffsetProp();
    const offset = parseInt(block.style[offsetProp] || 0) + 40;
    block.style[offsetProp] = offset + 'px';
};

export default {
    name: 'indent',

    order: 30,

    group: 'indent-level',

    icon: '<span class="fa fa-indent" aria-hidden="true"></span>',

    attributes: {
        title: __('oro.cms.wysiwyg.simple_actions.indent.title')
    },

    result(rte) {
        increaseOffset(rte);
    },

    state(view, doc, rte) {
        const selection = rte.selection();
        if (selection.type === 'None') {
            return 0;
        }

        const range = selection.getRangeAt(0);
        const container = range.commonAncestorContainer;

        const listType = findClosestListType(container);

        if (listType) {
            const depth = getParentsUntil(container, rte.el)
                .filter(tag => tag.tagName === (listType === 'ordered' ? 'OL' : 'UL')).length;

            return depth < ListMixin.listDepth ? 0 : -1;
        }
    }
};
