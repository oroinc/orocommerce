import {isRTL} from 'underscore';

export const getOffsetProp = () => isRTL() ? 'padding-right' : 'padding-left';

export const tags = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p'];
export const formatting = ['b', 'i', 'u', 'strike', 'sup', 'sub'];
export const lists = ['ul', 'ol', 'li'];

export const isBlockFormatted = node =>
    node && node.nodeType === Node.ELEMENT_NODE && tags.includes(node.tagName.toLowerCase());
export const isFormattedText = node =>
    node && node.nodeType === Node.ELEMENT_NODE && formatting.includes(node.tagName.toLowerCase());
export const isContainLists = node =>
    node && node.nodeType === Node.ELEMENT_NODE && lists.includes(node.tagName.toLowerCase());
export const isTag = (node, tagName) =>
    node && node.nodeType === Node.ELEMENT_NODE && node.tagName.toLowerCase() === tagName.toLowerCase();

/**
 * Wrap node into wrapper node
 * @param {Element} node
 * @param {Element} wrapper
 */
export const surroundContent = (node, wrapper) => {
    if (node.nodeType !== Node.ELEMENT_NODE) {
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
    if (node.nodeType === Node.ELEMENT_NODE && ['ul', 'ol'].includes(node.tagName.toLowerCase())) {
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
    const found = nodes.find(item => item.nodeType === Node.ELEMENT_NODE && checkTag(item.tagName.toLowerCase()));

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
    return nodes.find(item => item.nodeType === Node.ELEMENT_NODE &&
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

export const cloneAttrs = (targetNode, referenceNode, {exclude = []} = {}) => {
    for (const attr of referenceNode.attributes) {
        if (!exclude.includes(attr.name)) {
            targetNode.setAttribute(attr.name, attr.value);
        }
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
    const offset = end ? (node.nodeType === Node.TEXT_NODE ? node.nodeValue.length : node.innerText.length) : 0;
    range.setStart(node, offset);

    selection.removeAllRanges();
    selection.addRange(range);

    node.nodeType === Node.ELEMENT_NODE ? node.focus() : node.parentNode.focus();
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

/**
 * Slice formatted tag via br's
 * @param {Node} node
 * @returns {(node|Array)}
 */
export const formattingNodeSliceToNodes = node => {
    if (node.nodeType !== Node.ELEMENT_NODE) {
        return [];
    }

    const tagName = node.tagName;
    const nodes = [...node.childNodes].map(child => {
        if (isTag(child, 'br')) {
            return child;
        }

        const element = document.createElement(tagName);
        element.append(child);
        cloneAttrs(element, node, {
            exclude: ['id']
        });

        return element;
    });

    node.after(...nodes);
    const parent = node.parentNode;
    node.remove();
    if (isFormattedText(parent)) {
        return formattingNodeSliceToNodes(parent);
    }

    return [nodes, parent];
};

export const getNodeSiblings = (node, {callback = () => true, direction = 'next'} = {}) => {
    const siblings = [];
    let sibling = node[`${direction}Sibling`];

    while (sibling) {
        if (typeof callback === 'function' && callback(sibling)) {
            siblings.push(sibling);
            sibling = sibling[`${direction}Sibling`];
        } else {
            sibling = null;
        }
    }

    return direction === 'previous' ? siblings.reverse() : siblings;
};
