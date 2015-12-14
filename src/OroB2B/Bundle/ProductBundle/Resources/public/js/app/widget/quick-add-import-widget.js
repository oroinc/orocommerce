/*jslint nomen:true*/
/*global define*/
define(function(require) {
    'use strict';

    var QuickAddImportWidget;
    var DialogWidget = require('oro/dialog-widget');

    QuickAddImportWidget = DialogWidget.extend({
        initialize: function(options) {
            this.options.stateEnabled = false;
            this.options.incrementalPosition = false;

            options.dialogOptions = {
                'modal': true,
                'resizable': false,
                'width': 680,
                'height': 350,
                'autoResize': true
            };

            QuickAddImportWidget.__super__.initialize.apply(this, arguments);
        }
    });

    return QuickAddImportWidget;
});
