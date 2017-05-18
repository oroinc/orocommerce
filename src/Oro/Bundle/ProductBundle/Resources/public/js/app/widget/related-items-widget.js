define(function(require) {
    'use strict';

    var RelatedItemsWidget;
    var DialogWidget = require('oro/dialog-widget');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');

    RelatedItemsWidget = DialogWidget.extend({
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            RelatedItemsWidget.__super__.initialize.apply(this, arguments);
        }
    });

    return RelatedItemsWidget;
});
