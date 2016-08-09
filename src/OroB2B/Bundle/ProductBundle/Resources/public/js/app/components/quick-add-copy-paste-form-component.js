define(function(require) {
    'use strict';

    var QuickAddCopyPasteFormComponent;
    var _ = require('underscore');
    var $ = require('jquery');
    var DialogWidget = require('oro/dialog-widget');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var mediator = require('oroui/js/mediator');

    QuickAddCopyPasteFormComponent = BaseComponent.extend({
        /**
         * {@inheritDoc}
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            this.options._sourceElement.on('submit', _.bind(this.onFormSubmit, this));
        },

        onFormSubmit: function(event) {
            this.form = $(event.target);

            event.preventDefault();

            this.form.validate();
            if (!this.form.valid()) {
                return false;
            }

            this.renderDialog();
        },

        renderDialog: function() {
            var self = this;
            this.dialogWidget =  new DialogWidget(this.options || {});
            this.dialogWidget.firstRun = false;
            this.dialogWidget.loadContent(this.form.serialize(), this.form.attr('method'), this.form.attr('action'));
            this.dialogWidget.on('contentLoad', function(content) {
                if (_.has(content, 'redirectUrl')) {
                    self.dialogWidget.dispose();
                    mediator.execute('showLoading');
                    mediator.execute('redirectTo', {url: content.redirectUrl}, {redirect: true});

                }
            });
        },

        dispose: function() {
            this.dialogWidget.off('contentLoad');
            delete this.dialogWidget;
            delete this.form;
        }
    });

    return QuickAddCopyPasteFormComponent;
});
