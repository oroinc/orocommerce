define(function(require) {
    'use strict';

    var ContentWidget;
    var DialogWidget = require('oro/dialog-widget');
    var $ = require('jquery');

    ContentWidget = DialogWidget.extend({
        initialize: function(options) {
            this.options.url = false;
            options.url = false;

            ContentWidget.__super__.initialize.apply(this, arguments);
        },

        render: function(options) {
            this.setElement($($(this.options.content).html()));
            ContentWidget.__super__.render.apply(this, arguments);
        }
    });

    return ContentWidget;
});
