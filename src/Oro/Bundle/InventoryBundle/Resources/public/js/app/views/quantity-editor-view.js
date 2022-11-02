define(function(require) {
    'use strict';

    const NumberEditorView = require('oroform/js/app/views/editor/number-editor-view');
    /** @var QuantityHelper QuantityHelper **/
    const QuantityHelper = require('oroproduct/js/app/quantity-helper');

    const DecimalsNumberEditorView = NumberEditorView.extend(/** @lends DecimalsNumberEditorView.prototype */{
        className: 'decimals-number-editor',
        numberOfDecimals: null,

        /**
         * @inheritdoc
         */
        constructor: function DecimalsNumberEditorView(options) {
            DecimalsNumberEditorView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
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
                    this.numberOfDecimals = numberOfDecimals;
                }
            }

            DecimalsNumberEditorView.__super__.initialize.call(this, options);
        },
        formatRawValue: function(value) {
            return QuantityHelper.formatQuantity(value, this.numberOfDecimals, true, true);
        },

        parseRawValue: function(value) {
            return QuantityHelper.getQuantityNumberOrDefaultValue(value, NaN);
        },

        getValue: function() {
            const userInput = this.$('input[name=value]').val();
            if (userInput === '') {
                return 0;
            }

            const parsed = this.parseRawValue(userInput);
            if (parsed === false) {
                return NaN;
            }

            return parsed;
        }
    });

    return DecimalsNumberEditorView;
});
