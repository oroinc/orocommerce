/**
 * @plugin
 * GrapesJS plugin
 *
 * Extend HTML parser
 */

const postParseModel = model => {
    if (model.components) {
        if (typeof model.components === 'string') {
            model.components = [{
                type: 'textnode',
                content: model.components
            }];
        }

        if (model.components.length) {
            const isTextContain = model.components.some(
                ({type, textComponent}) => ['textnode', 'text'].includes(type) || textComponent
            );

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

    return model;
};

/**
 * @constructor
 * Content parser initialize
 * @param editor
 * @constructor
 */
export default function ContentParser(editor) {
    const originParseNode = editor.Parser.parserHtml.parseNode;
    editor.Parser.parserHtml.parseNode = (...args) =>
        originParseNode.apply(editor.Parser.parserHtml, args).map(postParseModel);

    editor.Parser.parseTextBlockContentFromString = html => {
        return editor.Parser.parseHtml(`<div>${html}</div>`).html[0].components;
    };
}
