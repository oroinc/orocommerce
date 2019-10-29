define(function(require) {
    'use strict';

    const NumberEditorView = require('oroform/js/app/views/editor/number-editor-view');

    const DecimalsNumberEditorView = NumberEditorView.extend(/** @lends DecimalsNumberEditorView.prototype */{
        className: 'decimals-number-editor',

        /**
         * @inheritDoc
         */
        constructor: function DecimalsNumberEditorView(options) {
            DecimalsNumberEditorView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            if (typeof options.decimalsField !== 'undefined') {
                options.decimals = parseInt(options.model.get(options.decimalsField));
            }

            const decimalsNumberValidator = options.validationRules.DecimalsNumber;
            if (typeof decimalsNumberValidator !== 'undefined') {
                if (typeof decimalsNumberValidator.decimalsField !== 'undefined') {
                    const numberOfDecimals = parseInt(options.model.get(decimalsNumberValidator.decimalsField));
                    decimalsNumberValidator.decimals = numberOfDecimals;
                }
            }

            DecimalsNumberEditorView.__super__.initialize.call(this, options);
        }
    });

    return DecimalsNumberEditorView;
});
