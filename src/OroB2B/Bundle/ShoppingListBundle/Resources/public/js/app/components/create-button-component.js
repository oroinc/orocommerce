/*jslint nomen:true*/
/*global define*/
define(function(require) {
    'use strict';

    var CreateButtonComponent;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var ShoppingListWidget = require('orob2bshoppinglist/js/app/widget/shopping-list-widget');
    var mediator = require('oroui/js/mediator');
    var routing = require('routing');
    var _ = require('underscore');

    CreateButtonComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {},

        /**
         * @property {jQuery.Element}
         */
        dialog: null,

        /**
         * @param {Object} options
         */
        initialize: function(options) {
            _.extend(this.options, options || {});

            this.options._sourceElement.on('click', 'a', _.bind(this.onClick, this));
        },

        onClick: function() {
            this.dialog = new ShoppingListWidget({});
            this.dialog.setUrl(routing.generate('orob2b_shopping_list_frontend_create', {createOnly: true}));

            this.dialog.on('formSave', _.bind(function() {
                mediator.execute('redirectTo', {url: window.location.href}, {redirect: true});
            }, this));

            this.dialog.render();
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.options._sourceElement.off();

            CreateButtonComponent.__super__.dispose.call(this);
        }
    });

    return CreateButtonComponent;
});
