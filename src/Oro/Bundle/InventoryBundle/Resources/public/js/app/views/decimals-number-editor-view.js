define(function(require) {
    'use strict';

    var DecimalsNumberEditorView;
    var NumberEditorView = require('oroform/js/app/views/editor/number-editor-view');

    DecimalsNumberEditorView = NumberEditorView.extend(/** @lends DecimalsNumberEditorView.prototype */{
        className: 'decimals-number-editor',

        /**
         * @inheritDoc
         */
        constructor: function DecimalsNumberEditorView() {
            DecimalsNumberEditorView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            if (typeof options.decimalsField !== 'undefined') {
                options.decimals = parseInt(options.model.get(options.decimalsField));
            }

            var decimalsNumberValidator = options.validationRules.DecimalsNumber;
            if (typeof decimalsNumberValidator !== 'undefined') {
                if (typeof decimalsNumberValidator.decimalsField !== 'undefined') {
                    var numberOfDecimals = parseInt(options.model.get(decimalsNumberValidator.decimalsField));
                    decimalsNumberValidator.decimals = numberOfDecimals;
                }
            }

            DecimalsNumberEditorView.__super__.initialize.apply(this, arguments);
        }
    });

    return DecimalsNumberEditorView;
});
