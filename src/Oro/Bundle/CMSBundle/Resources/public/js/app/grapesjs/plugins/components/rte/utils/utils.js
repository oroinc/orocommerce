import {isRTL} from 'underscore';

export const getOffsetProp = () => isRTL() ? 'padding-right' : 'padding-left';

export const tags = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p'];
export const formatting = ['b', 'i', 'u', 'strike', 'sup', 'sub'];
export const lists = ['ul', 'ol', 'li'];

export const isBlockFormatted = node => node.nodeType === 1 && tags.includes(node.tagName.toLowerCase());
export const isFormattedText = node => node.nodeType === 1 && formatting.includes(node.tagName.toLowerCase());
export const isContainLists = node => node.nodeType === 1 && lists.includes(node.tagName.toLowerCase());
export const isTag = (node, tagName) => node.nodeType === 1 && node.tagName.toLowerCase() === tagName.toLowerCase();

/**
 * Wrap node into wrapper node
 * @param {Element} node
 * @param {Element} wrapper
 */
export const surroundContent = (node, wrapper) => {
    if (node.nodeType !== 1) {
        return node;
    }
    [...node.childNodes].forEach(child => wrapper.append(child));
    node.appendChild(wrapper);
};

/**
 * Remove parent for node
 * @param {Node} node
 */
export const unwrap = node => {
    const parent = node.parentNode;
    while (node.firstChild) {
        parent.insertBefore(node.firstChild, node);
    }
    parent.removeChild(node);
};

/**
 * Surround node with surrounding tag
 * @param {Document} context
 * @returns {(function(*, *): void)|*}
 */
export function makeSurroundNode(context) {
    return (node, surround) => {
        const parent = context.createElement(surround);
        clearTextFormatting(node);
        surroundContent(node, parent);
    };
};

/**
 * Clear all formatting tags for node
 * @param {Node} node
 */
export function clearTextFormatting(node) {
    if (node.childNodes.length) {
        node.childNodes.forEach(child => {
            if (isBlockFormatted(child)) {
                unwrap(child);
                clearTextFormatting(child);
            }
        });
    }
};

/**
 * Find all formatting tags in selection range
 * @param {Range} range
 * @param {Element} node
 * @returns {(string|Array)}
 */
export function findTextFormattingInRange(range, node = null) {
    return [...(node ? node.childNodes : range.commonAncestorContainer.childNodes)].reduce((tags, child) => {
        if (!range.intersectsNode(child)) {
            return tags;
        }

        if (isBlockFormatted(child)) {
            tags.push(child.tagName.toLowerCase());
        }

        return tags.concat(...findTextFormattingInRange(range, child));
    }, []);
};

/**
 * Find closest parent as formatting tag
 * @param {Element} node
 * @returns {Element|*}
 */
export function findClosestFormattingBlock(node) {
    if (isBlockFormatted(node)) {
        return node;
    }

    if (!node.parentNode) {
        return;
    }

    return findClosestFormattingBlock(node.parentNode);
};

/**
 * Find closest list node and detect list type
 * @param {Element} node
 * @returns {string|*}
 */
export function findClosestListType(node) {
    if (node.nodeType === 1 && ['ul', 'ol'].includes(node.tagName.toLowerCase())) {
        return node.tagName === 'UL' ? 'unordered' : 'ordered';
    }

    if (!node.parentNode) {
        return;
    }

    return findClosestListType(node.parentNode);
}

/**
 * Get all parent until element
 * @param {Element} node
 * @param {Element} until
 * @returns {(Element|Array)}
 */
export function getParentsUntil(node, until = document) {
    const parents = [];

    for (;node && node !== until; node = node.parentNode) {
        parents.push(node);
    }
    return parents;
};

/**
 * Find closest
 * @param {Node} node
 * @param {string|(string|Array)} tagName
 * @param {boolean} reversed
 * @returns {Node}
 */
export function findParentTag(node, tagName = 'span', reversed = false) {
    const checkTag = tag => Array.isArray(tagName) ? tagName.includes(tag) : tag === tagName.toLowerCase();

    const nodes = reversed ? getParentsUntil(node) : getParentsUntil(node).reverse();
    const found = nodes.find(item => item.nodeType === 1 && checkTag(item.tagName.toLowerCase()));

    if (found) {
        return found;
    }

    return node;
};

/**
 *
 * @param {Element} node
 * @param {string|(string|Array)}tagName
 * @param {boolean} reversed
 * @returns {Node}
 */
export function foundClosestParentByTagName(node, tagName, reversed = false) {
    const nodes = reversed ? getParentsUntil(node) : getParentsUntil(node).reverse();
    return nodes.find(item => item.nodeType === 1 &&
        (Array.isArray(tagName) ? tagName.includes(item.tagName.toLowerCase()) : item.tagName.toLowerCase() === tagName)
    );
};

export function changeTagName(node, tagName) {
    const newNode = document.createElement(tagName);
    cloneAttrs(newNode, node);

    for (const child of [...node.childNodes]) {
        newNode.append(child);
    }
    node.parentNode.insertBefore(newNode, node);
    node.remove();
    return newNode;
};

export const cloneAttrs = (targetNode, referenceNode) => {
    for (const attr of referenceNode.attributes) {
        targetNode.setAttribute(attr.name, attr.value);
    }
};

export const saveCursor = rte => {
    const selection = rte.selection();
    const range = selection.getRangeAt(0);
    const {type} = selection;
    const {startContainer, endContainer, startOffset, endOffset} = range;

    return (offset = 0) => {
        const range = rte.doc.createRange();

        if (!rte.doc.body.contains(startContainer) || !rte.doc.body.contains(endContainer)) {
            range.setStart(rte.el.lastChild, 0);
            range.collapse(true);
        } else {
            if (type === 'Caret') {
                range.setStart(startContainer, startOffset + offset);
                range.collapse(true);
            }
            if (type === 'Range') {
                range.setStart(startContainer, startOffset + offset);
                range.setEnd(endContainer, endOffset + offset);
            }
        }

        const sel = rte.doc.getSelection();
        sel.removeAllRanges();
        sel.addRange(range);
        range.commonAncestorContainer.parentNode && range.commonAncestorContainer.parentNode.focus();
    };
};

/**
 * Focus on node
 * @param {Object} rte
 * @param {Element} node
 * @param {boolean} end
 */
export const focusCursor = (rte, node, end = false) => {
    const selection = rte.selection();
    const range = rte.doc.createRange();
    const offset = end ? (node.nodeType === 3 ? node.nodeValue.length : node.innerText.length) : 0;
    range.setStart(node, offset);

    selection.removeAllRanges();
    selection.addRange(range);

    node.nodeType === 1 ? node.focus() : node.parentNode.focus();
};

/**
 * Check if selection contain node with need tag name
 * @param {Object} rte
 * @param {string} tagName
 * @returns {boolean}
 */
export const isTagUnderSelection = (rte, tagName = 'A') => {
    const {anchorNode, focusNode} = rte.selection();
    const parentAnchor = anchorNode?.parentNode;
    const parentFocus = focusNode?.parentNode;
    return parentAnchor?.nodeName === tagName || parentFocus?.nodeName === tagName;
};
