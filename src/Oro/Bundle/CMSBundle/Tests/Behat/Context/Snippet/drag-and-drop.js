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
    const EVENT_THRESHOLD = 40;

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
     * @param {HTMLElement} el
     * @returns {DragEvent}
     */
    function createDragEvent(type, dataTransfer, el) {
        const opts = {};

        if (el) {
            const {x, y, width, height} = el.getBoundingClientRect();
            Object.assign(opts, {
                clientX: x + width / 2,
                clientY: y + height / 2
            });
        }

        const event = new DragEvent(type, {bubbles: true, cancelable: true, detail: null, ...opts});
        event.dataTransfer = dataTransfer || new DataTransfer();

        return event;
    }

    /**
     * @param {String} type
     * @param {HTMLElement} el
     * @returns {MouseEvent}
     */
    function createMouseEvent(type, el) {
        const {x, y, width, height} = el.getBoundingClientRect();
        return new MouseEvent(type, {
            bubbles: true,
            cancelable: true,
            detail: null,
            clientX: x + width / 2,
            clientY: y + height / 2
        });
    }

    function createDispatcher() {
        let timeout = 0;
        return cb => {
            setTimeout(cb, timeout);
            timeout += EVENT_THRESHOLD;
        };
    }

    const EVENT_TYPES = {
        DRAG_START: 'dragstart',
        DRAG_ENTER: 'dragenter',
        DRAG_OVER: 'dragover',
        DRAG_END: 'dragend',
        DROP: 'drop',
        MOUSEMOVE: 'mousemove'
    };

    const sourceContextNode = findByXpath(sourceContextNodePath || '/');
    const sourceNode = findByXpath(sourcePath, sourceContextNode);
    const destinationContextNode = findByXpath(destinationContextNodePath || sourceContextNodePath || '/');
    const destinationNode = findByXpath(destinationPath, destinationContextNode);

    const event = createDragEvent(EVENT_TYPES.DRAG_START, null, sourceNode);

    const dispatcher = createDispatcher();

    dispatcher(() => sourceNode.dispatchEvent(event));
    dispatcher(() => destinationNode.dispatchEvent(createDragEvent(
        EVENT_TYPES.DRAG_ENTER,
        event.dataTransfer,
        destinationNode
    )));
    dispatcher(() => destinationNode.dispatchEvent(createMouseEvent(EVENT_TYPES.MOUSEMOVE, destinationNode)));
    dispatcher(() => destinationNode.dispatchEvent(createDragEvent(
        EVENT_TYPES.DRAG_OVER,
        event.dataTransfer,
        destinationNode
    )));
    dispatcher(() => destinationNode.dispatchEvent(createDragEvent(
        EVENT_TYPES.DROP,
        event.dataTransfer,
        destinationNode
    )));
    dispatcher(() => sourceNode.dispatchEvent(createDragEvent(EVENT_TYPES.DRAG_END, event.dataTransfer, sourceNode)));
}
