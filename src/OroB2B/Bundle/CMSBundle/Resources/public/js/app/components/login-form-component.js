/*jslint nomen:true*/
/*global define*/
define(function(require) {
    'use strict';

    var LoginFormComponent;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var _ = require('underscore');

    LoginFormComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            item_selector: 'button'
        },

        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            this.options._sourceElement.on('submit', _.bind(this.onSubmit, this));
        },

        onSubmit: function() {
            this.options._sourceElement.find(this.options.item_selector).attr('disabled', 'disabled');
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.options._sourceElement.off();

            LoginFormComponent.__super__.dispose.call(this);
        }
    });

    return LoginFormComponent;
});
