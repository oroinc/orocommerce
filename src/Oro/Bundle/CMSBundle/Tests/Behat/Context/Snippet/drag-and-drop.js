/**
 * @param {String} sourcePath XPath of the element to drag.
 * @param {String} destinationPath XPath of the element to which to drop.
 * @param {String} [sourceContextNodePath]  XPath of the context element for which sourcePath must be applied.
 *                                          Default is '/'.
 *                                          Should be the path to the GrapesJs iframe if you want to drag within
 *                                          GrapesJs WYSIWYG.
 * @param {String} [destinationContextNodePath] XPath of the context element for which destinationPath must be applied.
 *                                              Default equals to sourceContextNodePath.
 *                                              Should be the path to the GrapesJs iframe if you want to drop into
 *                                              GrapesJs WYSIWYG.
 */
// eslint-disable-next-line no-unused-vars
function dragAndDrop(sourcePath, destinationPath, sourceContextNodePath, destinationContextNodePath) {
    /**
     * @param {String} xpathExpression
     * @param {Node} [contextNode]
     * @returns {Node}
     */
    function findByXpath(xpathExpression, contextNode) {
        const element = document
            .evaluate(xpathExpression, contextNode || document, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null)
            .singleNodeValue;

        if (!element) {
            throw new Error('Could not find element by XPath: ' + xpathExpression);
        }

        return element instanceof HTMLIFrameElement ? element.contentDocument : element;
    }

    /**
     * @param {String} type
     * @param {DataTransfer} [dataTransfer]
     * @returns {CustomEvent}
     */
    function createCustomEvent(type, dataTransfer) {
        const event = new CustomEvent(type, {bubbles: true, cancelable: true, detail: null});
        event.dataTransfer = dataTransfer || new DataTransfer();

        return event;
    }

    const EVENT_TYPES = {
        DRAG_START: 'dragstart',
        DRAG_ENTER: 'dragenter',
        DRAG_OVER: 'dragover',
        DRAG_END: 'dragend',
        DROP: 'drop'
    };

    const sourceContextNode = findByXpath(sourceContextNodePath || '/');
    const sourceNode = findByXpath(sourcePath, sourceContextNode);
    const destinationContextNode = findByXpath(destinationContextNodePath || sourceContextNodePath || '/');
    const destinationNode = findByXpath(destinationPath, destinationContextNode);

    const event = createCustomEvent(EVENT_TYPES.DRAG_START);
    sourceNode.dispatchEvent(event);

    destinationNode.dispatchEvent(createCustomEvent(EVENT_TYPES.DRAG_ENTER, event.dataTransfer));
    destinationNode.dispatchEvent(createCustomEvent(EVENT_TYPES.DRAG_OVER, event.dataTransfer));
    destinationNode.dispatchEvent(createCustomEvent(EVENT_TYPES.DROP, event.dataTransfer));
    sourceNode.dispatchEvent(createCustomEvent(EVENT_TYPES.DRAG_END, event.dataTransfer));
}
