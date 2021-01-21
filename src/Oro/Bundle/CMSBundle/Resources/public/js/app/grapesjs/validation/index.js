import __ from 'orotranslation/js/translator';
import BaseClass from 'oroui/js/base-class';
import idCollision from './id-colision';
import reservedId from './reserved-id.js';

const HTMLValidator = BaseClass.extend({
    /**
     * Constraints method collections
     */
    constraints: [
        idCollision,
        reservedId
    ],

    lineMessage: 'oro.htmlpurifier.formatted_error_line',

    constructor: function HTMLValidator(options) {
        HTMLValidator.__super__.constructor.call(this, options);
    },

    validate(htmlString) {
        const errors = [];
        const domParser = new DOMParser();
        const htmlFragment = domParser.parseFromString(htmlString, 'text/html');
        const htmlStringLines = htmlString.split('\n');

        const constraints = this.constraints.map(constraint => {
            const cache = {};
            return params => constraint({cache, ...params});
        });

        htmlStringLines.forEach((htmlStringLine, index) => {
            const lineNumber = index + 1;

            for (const constraint of constraints) {
                const errorMessage = constraint({
                    htmlStringLine,
                    htmlString,
                    htmlStringLines,
                    htmlFragment,
                    lineNumber
                });

                if (errorMessage) {
                    errors.push(this.prepareErrorData(lineNumber, errorMessage));
                }
            }
        });

        return errors;
    },

    prepareErrorData(lineNumber, subMessage) {
        return {
            line: lineNumber,
            message: __(this.lineMessage, {
                line: lineNumber,
                message: subMessage
            })
        };
    }
});

export default HTMLValidator;
