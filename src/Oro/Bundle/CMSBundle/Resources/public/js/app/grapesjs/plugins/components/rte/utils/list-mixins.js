import {
    findParentTag,
    isBlockFormatted,
    cloneAttrs,
    saveCursor,
    foundClosestParentByTagName,
    changeTagName,
    getParentsUntil,
    getOffsetProp,
    focusCursor,
    isFormattedText,
    isTag,
    formattingNodeSliceToNodes,
    getNodeSiblings,
    findClosestFormattingBlock
} from './utils';

export default class ListMixin {
    constructor(listType, oppositeListType) {
        this.listType = listType;
        this.oppositeListType = oppositeListType;
        this.execCommandName = listType === 'UL' ? 'insertUnorderedList' : 'insertOrderedList';
    }

    dispatchKeyDownEvent(rte, editor, event) {
        switch (event.keyCode) {
            case 8:
                this.processBackspace(rte, editor, event);
                break;
            case 9:
                this.processSubList(rte, editor, event.shiftKey);
                break;
            case 13:
                if (!event.shiftKey) {
                    this.processEnter(rte, editor, event);
                }
                break;
            case 90:
                if (!event.shiftKey && (event.ctrlKey || event.metaKey)) {
                    this.processUndo(rte, editor, event);
                }
                break;
        }
    }

    mergeLists(list) {
        const prev = list.previousSibling;
        let next = list.nextSibling;

        if (isTag(next, 'br')) {
            next.remove();
            next = list.nextSibling;
        }

        if (isTag(prev, this.listType)) {
            [...prev.childNodes].reverse().forEach(child => {
                list.prepend(child);
            });

            cloneAttrs(list, prev);

            prev.remove();
        }

        if (isTag(next, this.listType)) {
            [...next.childNodes].forEach(child => {
                list.append(child);
            });

            cloneAttrs(list, next);

            next.remove();
        }
    }

    separateList(list, separator) {
        const cloneList = list.cloneNode();

        const moveItems = this.getNextSiblings(separator);

        list.after(separator);

        if (moveItems.length) {
            moveItems.forEach(item => cloneList.appendChild(item));
            separator.after(cloneList);
        }
    }

    getNextSiblings(node) {
        const siblings = [];
        let current = node.nextSibling;

        while (current) {
            if (current.nodeType === Node.ELEMENT_NODE) {
                siblings.push(current);
            }
            current = current.nextSibling;
        }
        return siblings;
    }

    clearOffset(node) {
        if (node.nodeType !== Node.ELEMENT_NODE) {
            return;
        }
        const offsetProp = getOffsetProp();
        const offset = node.style[offsetProp];

        if (offset && parseInt(offset) % 40 === 0) {
            node.style.removeProperty(offsetProp);
        }
    }

    checkDepth(container, element) {
        return getParentsUntil(container, element)
            .filter(tag => tag.tagName === this.listType).length < ListMixin.listDepth;
    }

    isSubList(container, element) {
        return getParentsUntil(container, element)
            .filter(tag => tag.tagName === this.listType).length > 1;
    }

    insertNodeAfter(reference, nodeName = 'LI', content) {
        const listItem = document.createElement(nodeName);
        if (content) {
            listItem.append(content);
        }
        reference.after(listItem);

        return listItem;
    }

    beforeListInsert(nodes) {
        const firstNode = nodes[0];
        const lastNode = nodes[nodes.length - 1];
        const isFormatted = node => node.nodeType === Node.TEXT_NODE || isFormattedText(node);

        const prevSiblings = firstNode && isFormatted(firstNode) ? getNodeSiblings(firstNode, {
            callback: isFormatted,
            direction: 'previous'
        }) : [];

        const nextSiblings = lastNode && isFormatted(lastNode) ? getNodeSiblings(lastNode, {
            callback: isFormatted
        }) : [];

        return [...prevSiblings, ...nodes, ...nextSiblings];
    }

    insertNodeToList(node) {
        const list = document.createElement(this.listType);
        node.parentNode.insertBefore(list, node);
        const listItem = document.createElement('LI');
        listItem.append(node);
        this.clearOffset(node);
        list.append(listItem);

        this.mergeLists(list);
    }

    insertNodesToList(nodes, parent) {
        nodes = this.beforeListInsert(nodes);
        const list = document.createElement(this.listType);
        parent.insertBefore(list, nodes[0]);

        for (const node of this.chunkNodes(nodes)) {
            const listItem = document.createElement('LI');
            this.clearOffset(node);
            Array.isArray(node) ? node.forEach(n => listItem.append(n)) : listItem.append(node);
            list.append(listItem);

            if (listItem.firstChild.nodeType === Node.ELEMENT_NODE && listItem.firstChild.tagName === this.listType) {
                listItem.classList.add('list-style-none');
            }
        }

        this.mergeLists(list);
    }

    chunkNodes(nodes) {
        const chunks = [];
        const isList = list => list && list.nodeType === Node.ELEMENT_NODE && list.tagName === this.listType;
        for (const node of nodes) {
            const key = chunks.length - 1;
            if (isBlockFormatted(node) ||
                node.tagName === 'BR' ||
                node.tagName === 'DIV' ||
                isList(node.previousSibling)
            ) {
                chunks.push(node);
            } else if (isList(node)) {
                if (!chunks[key]) {
                    chunks.push([node]);
                }
                if (Array.isArray(chunks[key])) {
                    chunks[key] = [...chunks[key], node];
                } else {
                    chunks[key] = [chunks[key], node];
                }
            } else if (isFormattedText(node) || isTag(node, 'span')) {
                if (Array.isArray(chunks[key])) {
                    chunks[key] = [...chunks[key], node];
                } else if (chunks.length === 1 && chunks[key].nodeType === Node.TEXT_NODE) {
                    chunks[key] = [chunks[key], node];
                } else {
                    chunks.push([node]);
                }
            } else {
                if (Array.isArray(chunks[key])) {
                    chunks[key] = [...chunks[key], node];
                } else {
                    chunks.push([node]);
                }
            }
        }

        return chunks.filter(chunk => {
            if (!Array.isArray(chunk) && chunk.tagName === 'BR') {
                chunk.remove();

                return false;
            }

            return true;
        });
    }

    extractNodesFromList(nodes) {
        const list = findParentTag(nodes, this.listType.toLowerCase());
        const listItems = list.querySelectorAll('li');
        const subLists = list.querySelectorAll(this.listType);
        const parent = list.parentNode;
        const count = list.childNodes.length;

        if (list.previousSibling && list.previousSibling.nodeType === Node.TEXT_NODE) {
            parent.insertBefore(document.createElement('BR'), list);
        }

        [...listItems].forEach((child, index) => {
            if (child.childNodes.length) {
                if (
                    !(
                        isBlockFormatted(child.firstChild) ||
                        [this.listType, 'DIV'].includes(child.firstChild.tagName)
                    ) ||
                    (child.childNodes[child.childNodes.length - 1].nodeType === Node.TEXT_NODE && index < count - 1)
                ) {
                    child.append(document.createElement('BR'));
                }

                [...child.childNodes].forEach(subChild => {
                    parent.insertBefore(subChild, list);
                });
            }

            child.remove();
        });

        [...subLists].forEach(subList => subList.remove());
        list.remove();
    }

    processUndo(rte, editor) {
        const {container} = this.exposeSelection(rte);

        const undoLi = node => {
            const prev = node.previousSibling;
            if (prev) {
                focusCursor(rte, prev.firstChild, true);
            }
            node.remove();
            editor.trigger('change:canvasOffset');
        };

        if (container.nodeType === Node.TEXT_NODE) {
            const node = findParentTag(container, 'li', true);
            undoLi(node);
            return;
        }

        if (container.nodeType === Node.ELEMENT_NODE && container.tagName === 'LI' && !container.childNodes.length) {
            undoLi(container);
        }
    }

    processEnter(rte, editor, event) {
        event.preventDefault();
        const {container, element, selection, range} = this.exposeSelection(rte);
        const list = findParentTag(container, this.listType.toLowerCase(), true);
        const node = findParentTag(container, 'li', true);

        let newNode;
        if (node.innerHTML.length) {
            let offsetNode;
            if (selection.type === 'Caret' && container.nodeType === Node.TEXT_NODE) {
                offsetNode = container.splitText(range.startOffset);
            }

            if (isBlockFormatted(container.parentNode)) {
                const formatted = document.createElement(container.parentNode.tagName);
                formatted.append(offsetNode);
                offsetNode = formatted;
            }

            newNode = this.insertNodeAfter(node, 'li', offsetNode);
            setTimeout(() => focusCursor(rte, offsetNode ? offsetNode : newNode), 0);
        } else {
            if (this.isSubList(container, element)) {
                this.processSubList(rte, editor, true);
            } else {
                this.separateList(list, node);
                newNode = this.insertNodeAfter(node, 'p');
                newNode.append(document.createElement('br'));
                node.after(newNode);
                node.remove();
                setTimeout(() => focusCursor(rte, newNode), 0);
            }
        }

        editor.trigger('change:canvasOffset');
        event.preventDefault();
    }

    processBackspace(rte, editor, event) {
        const {container} = this.exposeSelection(rte);

        const list = findParentTag(container, this.listType.toLowerCase(), true);
        const node = findParentTag(container, 'li', true);

        if (list.parentNode.tagName !== 'LI') {
            return;
        }

        if (!node.innerText.replace(/\n/gi, '').length) {
            this.processSubList(rte, editor, true);
            if (!node.childNodes.length) {
                node.classList.add('list-style-none');
            }
            event.preventDefault();
        }

        this.mergeLists(list);
    }

    processSubList(rte, editor, remove = false) {
        const {selection, range, cursor, container, element} = this.exposeSelection(rte);

        const removeSubList = () => {
            const list = findParentTag(container, this.listType.toLowerCase(), true);
            const node = findParentTag(container, 'li', true);
            const referenceNode = findParentTag(list, 'li', true);

            if (referenceNode.tagName !== 'LI') {
                return;
            }

            referenceNode.after(node);

            const oldReference = list.querySelector('.list-style-none');
            if (oldReference && !oldReference.childNodes.length) {
                oldReference.remove();
            }

            if (!list.childNodes.length) {
                list.remove();
            }

            if (referenceNode.classList.contains('list-style-none') && !referenceNode.childNodes.length) {
                referenceNode.remove();
            }

            editor.trigger('change:canvasOffset');
            cursor();
        };

        const createSubList = () => {
            const node = findParentTag(container, 'li', true);
            const referenceList = findParentTag(container, this.listType.toLowerCase(), true);
            let reference = node.previousSibling;
            if (!reference) {
                reference = document.createElement('LI');
                referenceList.prepend(reference);
            }

            const list = document.createElement(this.listType);

            list.append(node);
            if (!reference.childNodes.length) {
                reference.classList.add('list-style-none');
            }
            reference.append(list);
            this.mergeLists(list);
            editor.trigger('change:canvasOffset');
            cursor();
        };

        if (!this.checkDepth(container, element) && !remove) {
            return;
        }

        if (selection.type === 'Caret') {
            return remove ? removeSubList() : createSubList();
        }

        if (selection.type === 'Range') {
            if (remove) {
                if (container.nodeType === Node.TEXT_NODE) {
                    return removeSubList();
                }

                if (isBlockFormatted(container) || isTag(container, 'span')) {
                    removeSubList();
                    cursor();
                    editor.trigger('change:canvasOffset');
                    return;
                }

                const node = findParentTag(container, 'li', true);
                const nodes = [...container.childNodes].filter(node => range.intersectsNode(node));

                if (node.parentNode.tagName !== this.listType) {
                    return;
                }

                for (const index in nodes) {
                    if (index === '0') {
                        node.after(nodes[index]);
                    } else {
                        nodes[index - 1].after(nodes[index]);
                    }
                }

                const oldReference = container.querySelector('.list-style-none');
                if (oldReference && !oldReference.childNodes.length) {
                    oldReference.remove();
                }

                if (!container.childNodes.length) {
                    container.remove();
                }

                if (node.classList.contains('list-style-none') && !node.childNodes.length) {
                    node.remove();
                }

                editor.trigger('change:canvasOffset');
                cursor();

                return;
            } else {
                if (container.nodeType === Node.TEXT_NODE) {
                    return createSubList();
                }

                if (isBlockFormatted(container) || isTag(container, 'span')) {
                    createSubList();
                    cursor();
                    editor.trigger('change:canvasOffset');
                    return;
                }

                const nodes = [...container.childNodes].filter(node => range.intersectsNode(node));
                let reference = nodes[0].previousSibling;
                const list = document.createElement(this.listType);

                if (!reference) {
                    reference = document.createElement('LI');
                    container.prepend(reference);
                }

                for (const node of nodes) {
                    list.append(node);
                }

                if (!reference.childNodes.length) {
                    reference.classList.add('list-style-none');
                }
                reference.append(list);
                this.mergeLists(list);
                editor.trigger('change:canvasOffset');
                cursor();
            }
        }
    }

    processList(rte, editor) {
        const {selection, range, isList, cursor, container} = this.exposeSelection(rte);

        const foundOlList = foundClosestParentByTagName(container, this.oppositeListType.toLowerCase());
        if (foundOlList) {
            this.changeListType(foundOlList, this.listType.toLowerCase());
            cursor();
            editor.trigger('change:canvasOffset');
            return;
        }

        const makeList = () => {
            const parentNode = findClosestFormattingBlock(container);
            if (parentNode) {
                this.insertNodeToList(parentNode);
                editor.trigger('change:canvasOffset');
                cursor();
                return;
            }

            rte.exec(this.execCommandName);
            this.mergeLists(findParentTag(container, this.listType.toLowerCase()));
            editor.trigger('change:canvasOffset');
        };

        if (selection.type === 'Caret') {
            if (isList) {
                this.extractNodesFromList(container);
                editor.trigger('change:canvasOffset');
                cursor();
                return;
            } else {
                if (container.nodeType === Node.TEXT_NODE) {
                    return makeList();
                }
            }
        }

        if (selection.type === 'Range') {
            if (isList) {
                this.extractNodesFromList(container);
                editor.trigger('change:canvasOffset');
                cursor();
                return;
            } else {
                if (container.nodeType === Node.TEXT_NODE) {
                    return makeList();
                }

                if (isBlockFormatted(container) || isTag(container, 'span')) {
                    this.insertNodeToList(container);
                    cursor();
                    editor.trigger('change:canvasOffset');
                    return;
                }

                if (isFormattedText(container)) {
                    const [nodes, parent] = formattingNodeSliceToNodes(container);
                    this.insertNodesToList(nodes, parent);
                    cursor();
                    editor.trigger('change:canvasOffset');
                    return;
                }

                const nodes = [...container.childNodes].filter(node => range.intersectsNode(node));

                if (this.isTag(nodes[nodes.length - 1].nextSibling, 'br')) {
                    nodes.push(nodes[nodes.length - 1].nextSibling);
                }

                this.insertNodesToList(nodes, container);
                cursor();
            }
        }

        editor.trigger('change:canvasOffset');
    }

    isTag(node, tagName) {
        return node && node.nodeType === Node.ELEMENT_NODE && node.tagName.toLowerCase() === tagName.toLowerCase();
    }

    changeListType(list, type) {
        const subLists = list.querySelectorAll(list.tagName.toLowerCase());
        const newNode = changeTagName(list, type);

        subLists.forEach(subList => changeTagName(subList, type));
        this.mergeLists(newNode);
    }

    isList(rte) {
        return rte.doc.queryCommandSupported(this.execCommandName) && rte.doc.queryCommandState(this.execCommandName);
    }

    exposeSelection(rte) {
        const selection = rte.selection();
        const range = selection.getRangeAt(0);
        const cursor = saveCursor(rte);
        const element = rte.el;
        let container = range.commonAncestorContainer;

        if (this.isList(rte) &&
            container.tagName !== this.listType &&
            container.isEqualNode(rte.el)
        ) {
            container = container.querySelector(this.listType.toLowerCase());
        } else if (container.isEqualNode(rte.el) && container.childNodes.length === 1) {
            container = container.firstChild;
        }

        const isList = this.isList(rte) || container.tagName === this.listType;

        return {
            selection,
            range,
            isList,
            cursor,
            container,
            element
        };
    }
}

ListMixin.listDepth = 8;
