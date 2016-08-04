define(function(require) {
    'use strict';

    var QuickAddCopyPasteFormComponent;
    var _ = require('underscore');
    var $ = require('jquery');
    var DialogWidget = require('oro/dialog-widget');
    var BaseComponent = require('oroui/js/app/components/base/component');

    QuickAddCopyPasteFormComponent = BaseComponent.extend({
        /**
         * {@inheritDoc}
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            this.options._sourceElement.on('submit', _.bind(this.onFormSubmit, this));
        },

        onFormSubmit: function(event) {
            var widget;
            var form = $(event.target);

            form.validate();
            if (!form.valid()) {
                return false;
            }

            widget = new DialogWidget(this.options);

            widget.firstRun = false;
            widget.loadContent(form.serialize(), form.attr('method'), form.attr('action'));

            event.preventDefault();
        }
    });

    return QuickAddCopyPasteFormComponent;
});
