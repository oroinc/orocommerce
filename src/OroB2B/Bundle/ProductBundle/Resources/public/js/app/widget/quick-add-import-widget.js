define(function(require) {
    'use strict';

    var QuickAddImportWidget;
    var DialogWidget = require('oro/dialog-widget');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var mediator = require('oroui/js/mediator');

    QuickAddImportWidget = DialogWidget.extend({
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            QuickAddImportWidget.__super__.initialize.apply(this, arguments);
        },

        _onContentLoad: function(content) {
            if (_.has(content, 'redirectUrl')) {
                mediator.execute('redirectTo', {url: content.redirectUrl}, {redirect: true});
                return;
            }

            QuickAddImportWidget.__super__._onContentLoad.apply(this, arguments);
        }
    });

    return QuickAddImportWidget;
});
