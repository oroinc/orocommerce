/*jslint nomen:true*/
/*global define*/
define(function(require) {
    'use strict';

    var AddressBook;
    var BaseComponent = require('oroaddress/js/address-book');
    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');

    AddressBook = BaseComponent.extend({
        /**
         * @property {Object}
         */
        defaultOptions: {
            'useFormDialog': true
        },

        /**
         * @param {Object} options
         */
        initialize: function(options) {
            AddressBook.__super__.initialize.call(this, _.defaults(options || {}, this.defaultOptions));
        },

        /**
         * @param {String} title
         * @param {String} url
         * @private
         */
        _openAddressEditForm: function(title, url) {
            if (this.options.useFormDialog) {
                AddressBook.__super__._openAddressEditForm.apply(this, arguments);
            } else {
                mediator.execute('redirectTo', {url: url}, {redirect: true});
            }
        },

        addAll: function() {
            AddressBook.__super__.addAll.apply(this, arguments);

            var $manageAddressesLink = $(this.options.manageAddressesLink).clone();
            $manageAddressesLink.removeClass('hidden');
            $(this.options.manageAddressesLink).remove();
            this.$el.find('.map-address-list').append($manageAddressesLink);
        }
    });

    return AddressBook;
});
