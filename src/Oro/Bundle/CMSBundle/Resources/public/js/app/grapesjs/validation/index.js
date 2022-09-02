import __ from 'orotranslation/js/translator';
import {isObject} from 'underscore';
import BaseClass from 'oroui/js/base-class';
import idCollision from './id-colision';
import reservedId from './reserved-id.js';
import cssValidation from './css-validation.js';

const HTMLValidator = BaseClass.extend({
    editor: null,

    lineMessage: 'oro.htmlpurifier.formatted_error_line',

    constructor: function HTMLValidator({editor, ...rest}) {
        this.editor = editor;
        HTMLValidator.__super__.constructor.call(this, {editor, ...rest});
    },

    getConstraints({constraints = []} = {}) {
        return [
            idCollision,
            reservedId,
            cssValidation,
            ...constraints
        ];
    },

    validate(htmlString, options = {}) {
        const errors = [];
        const domParser = new DOMParser();
        const htmlStringLines = htmlString.split('\n');
        const htmlFragment = domParser.parseFromString(htmlString, 'text/html');

        const constraints = this.getConstraints(options).map(constraint => {
            const cache = {};
            return params => constraint({cache, ...params});
        });

        for (const index in htmlStringLines) {
            if (htmlStringLines.hasOwnProperty(index)) {
                const lineNumber = parseInt(index) + 1;

                for (const constraint of constraints) {
                    const errorMessage = constraint({
                        htmlStringLine: htmlStringLines[index],
                        htmlString,
                        htmlStringLines,
                        htmlFragment,
                        lineNumber,
                        editor: this.editor
                    });

                    if (typeof errorMessage === 'string') {
                        errors.push(this.prepareErrorData(lineNumber, errorMessage, options));
                    } else if (isObject(errorMessage)) {
                        const {lineNumber: errorLineNumber, message, shortMessage} = errorMessage;
                        errors.push(this.prepareErrorData(errorLineNumber, message, {...options, shortMessage}));
                    }
                }
            }
        }

        return errors;
    },

    prepareErrorData(lineNumber, subMessage, {lineMessage, shortMessage = ''}) {
        if (!lineMessage) {
            lineMessage = this.lineMessage;
        }

        return {
            line: lineNumber,
            shortMessage,
            message: __(lineMessage, {
                line: lineNumber,
                message: subMessage
            })
        };
    }
});

export default HTMLValidator;
