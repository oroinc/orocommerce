/*jslint nomen:true*/
/*global define*/
define(function(require) {
    'use strict';

    var AccountUser;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var _ = require('underscore');
    //var $ = require('jquery');
    /*    var mediator = require('oroui/js/mediator');
    var __ = require('orotranslation/js/translator');
    var Modal = require('oroui/js/modal');*/
    var widgetManager = require('oroui/js/widget-manager');

    AccountUser = BaseComponent.extend({

        /**
         * @property {Object}
         */
        options: {
            widgetAlias: null,
            accountFormId: null
        },

        /**
         * @property {Object}
         */
        accountForm: null,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            this.accountForm = this.options._sourceElement.find(this.options.accountFormId);
            this.accountForm.on('change', _.bind(this.reloadRoleWidget, this));
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.accountForm.off('change');
            AccountUser.__super__.dispose.call(this);
        },

        reloadRoleWidget: function() {
            widgetManager.getWidgetInstanceByAlias(this.options.widgetAlias, function(widget) {
                widget.render();
            });
        }
    });

    return AccountUser;
});
