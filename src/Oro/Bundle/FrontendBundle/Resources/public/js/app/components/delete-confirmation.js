define(function(require) {
    'use strict';

    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var Modal = require('oroui/js/modal');

    /**
     * Delete confirmation dialog
     *
     * @export  orofrontend/js/delete-confirmation
     * @class   orofrontend.DeleteConfirmation
     * @extends oroui.Modal
     */
    return Modal.extend({
        /** @property {String} */
        template: require('text!orofrontend/templates/delete-confirmation.html'),

        /** @property {String} */
        okButtonClass: 'btn ok',

        /** @property {String} */
        cancelButtonClass: 'btn cancel',

        /** @property {Boolean} */
        allowOk: true,

        /**
         * @param {Object} options
         */
        initialize: function(options) {
            //Set custom template settings
            var interpolate = {
                interpolate: /\{\{(.+?)\}\}/g,
                evaluate: /<%([\s\S]+?)%>/g
            };

            options = _.extend({
                title: __('Delete Confirmation'),
                okText: __('Yes, Delete'),
                cancelText: __('Cancel'),
                template: _.template(this.template, interpolate),
                allowOk: this.allowOk,
                okButtonClass: this.okButtonClass,
                cancelButtonClass: this.cancelButtonClass
            }, options);

            arguments[0] = options;
            Modal.prototype.initialize.apply(this, arguments);
        }
    });
});
