/**
 * @plugin
 * GrapesJS plugin
 *
 * Extend HTML parser
 */

const postParseModel = model => {
    /**
     * Move style props to attributes.style to display styles as inline
     * Need for "indent" and "outdent" action in RTE editor
     */
    if (model.style && model.type === 'text') {
        ['padding-left', 'padding-right'].map(prop => {
            if (model.style[prop]) {
                const styleProps = model.attributes?.style ? model.attributes?.style.split(';') : [];
                styleProps.push(`padding-left: ${model.style[prop]}`);

                model.attributes = {
                    ...model.attributes || {},
                    style: styleProps.join(';') + ';'
                };
                delete model.style[prop];
            }
        });
    }

    if (model.components) {
        if (model.components.length) {
            const isTextContain = model.components.every(
                ({type, textComponent}) => type === 'text' || textComponent
            ) || model.components.some(({type}) => type === 'textnode');

            if (!model.type && isTextContain) {
                model.type = 'text';
            }

            if (model.type === 'text' || model.textComponent) {
                model.components.forEach(component => {
                    if ((component.type && component.type !== 'text') || !component.components) {
                        return;
                    }

                    Object.assign(component, {
                        layerable: 0,
                        selectable: 0,
                        hoverable: 0,
                        editable: 0,
                        draggable: 0,
                        droppable: 0,
                        highlightable: 0
                    });
                });
            }
        }

        if (model.components.type && model.components.type === 'textnode') {
            model.components = [{
                ...model.components,
                tagName: ''
            }];
        }
    }

    if (model.attributes && model.attributes['data-type'] === 'temporary-container') {
        return model.components;
    }

    if (model.type !== 'textnode') {
        model.origin = true;
    }

    return model;
};

/**
 * @constructor
 * Content parser initialize
 * @param editor
 * @constructor
 */
export default function ContentParser(editor) {
    const originParseNodes = editor.Parser.parserHtml.parseNodes;
    const originParse = editor.Parser.parserHtml.parse;

    editor.Parser.parserHtml.parse = (content, options) => {
        for (const type of editor.Parser.parserHtml.compTypes) {
            if (typeof type.model.beforeParse === 'function') {
                content = type.model.beforeParse(content);
            }
        }

        return originParse.call(editor.Parser.parserHtml, content, options);
    };

    editor.Parser.parserHtml.parseNodes = (...args) =>
        originParseNodes.apply(editor.Parser.parserHtml, args).map(postParseModel).flat();

    const originDestroy = editor.destroy;
    editor.destroy = () => {
        const Parser = Object.create(
            Object.getPrototypeOf(editor.em.get('Parser')),
            Object.getOwnPropertyDescriptors(editor.em.get('Parser'))
        );
        originDestroy.call(editor);
        editor.em.set('Parser', Parser);
    };
}
