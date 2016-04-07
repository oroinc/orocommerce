define(function(require) {
    'use strict';

    var QuickAddImportWidget;
    var DialogWidget = require('oro/dialog-widget');
    var __ = require('orotranslation/js/translator');

    QuickAddImportWidget = DialogWidget.extend({
        initialize: function(options) {
            this.options.stateEnabled = false;
            this.options.incrementalPosition = false;
            this.options.title = __('orob2b.product.frontend.quick_add.import_validation.title');

            options.dialogOptions = {
                'modal': true,
                'resizable': false,
                'width': 820,
                'autoResize': true,
                'dialogClass': 'ui-dialog-no-scroll'
            };

            QuickAddImportWidget.__super__.initialize.apply(this, arguments);
        }
    });

    return QuickAddImportWidget;
});
