/** @lends DecimalsNumberEditorView */
define(function(require) {
    'use strict';

    var DecimalsNumberEditorView ;
    var NumberEditorView = require('oroform/js/app/views/editor/number-editor-view');
    var _ = require('underscore');

    DecimalsNumberEditorView  = NumberEditorView.extend(/** @exports DecimalsNumberEditorView .prototype */{
        className: 'decimals-number-editor',

        initialize: function(options) {
            if (!_.isNumber(options.decimals)) {
                options.decimals = parseInt(options.model.attributes[options.decimals]);
            }

            var decimalsNumberValidator = options.validationRules['DecimalsNumber'];
            if (typeof decimalsNumberValidator !== undefined) {
                if (!_.isNumber(decimalsNumberValidator.decimals)) {
                    decimalsNumberValidator.decimals = options.model.attributes[decimalsNumberValidator.decimals];
                }
            }

            DecimalsNumberEditorView.__super__.initialize.apply(this, arguments);
        }
    });

    return DecimalsNumberEditorView ;
});
