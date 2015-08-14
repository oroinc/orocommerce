/*jslint nomen:true*/
/*global define*/
define(function(require) {
    'use strict';

    var AccountUserRole,
        BaseComponent = require('oroui/js/app/components/base/component'),
        $ = require('jquery'),
        mediator = require('oroui/js/mediator');

    AccountUserRole = BaseComponent.extend({
        /**
         * @property {Object}
         */
        targetElement: null,

        /**
         * @property {Object}
         */
        appendElement: null,

        /**
         * @property {Object}
         */
        removeElement: null,

        initialize: function(options) {
            this.targetElement = $('#' + options.accountFieldId);
            this.appendElement = $('#' + options.appendFieldId);
            this.removeElement = $('#' + options.removeFieldId);

            var that = this;

            this.targetElement.on('change', function(e) {

                // Set datagrid account parameter when account selected
                mediator.trigger('datagrid:setParam:' + options.datagridName, 'account', e.added ? e.added.id : null);

                // Set datagrid role parameter when account unselected
                mediator.trigger('datagrid:setParam:' + options.datagridName, 'role', e.added ? null : options.role);
                mediator.trigger('datagrid:doRefresh:' + options.datagridName);
                that.appendElement.val(null);
                that.removeElement.val(null);
            });
        },

        dispose: function() {
            if (this.disposed || !this.targetElement) {
                return;
            }

            this.targetElement.off('change');

            AccountUserRole.__super__.dispose.call(this);
        }
    });

    return AccountUserRole;
});
