/*jslint nomen:true*/
/*global define*/
define(function(require) {
    'use strict';

    var BaseComponent = require('oroui/js/app/components/base/component');
    var mediator = require('oroui/js/mediator');
    var routing = require('routing');

    return BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            eventChannelId: null
        },

        /**
         * @param {Object} options
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            mediator.on('shopping-list:created', function() {
                mediator.execute('redirectTo', {
                    url: routing.generate('orob2b_shopping_list_frontend_view')
                }, {
                    redirect: true
                });
            });

            mediator.on('shopping-list-event:' + this.options.eventChannelId + ':update', this.updateDropdown, this);
        },

        /**
         *
         * @param updateData
         */
        updateDropdown: function(updateData) {
            this.options._sourceElement
                .find('.shopping-list-links__item--' + updateData.id + ' .shopping-list-links__text')
                .text(updateData.label);
        }
    });
});
