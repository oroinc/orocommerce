/*jslint nomen:true*/
/*global define*/
define(function(require) {
    'use strict';

    var AccountAddressBook;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var _ = require('underscore');
    var routing = require('routing');
    var AddressBook = require('orob2baccount/js/address-book');

    AccountAddressBook = BaseComponent.extend({
        /**
         * @property {Object}
         */
        defaultOptions: {
            'entityId': null,
            'addressListUrl': null,
            'addressCreateUrl': null,
            'addressUpdateRouteName': null,
            'currentAddresses': [],
            'useFormDialog': false
        },

        /**
         * @param {Object} options
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            /** @type oroaddress.AddressBook */
            var addressBook = new AddressBook({
                el: '#address-book',
                addressListUrl: options.addressListUrl,
                addressCreateUrl: options.addressCreateUrl,
                addressUpdateUrl: function() {
                    var address = arguments[0];
                    return routing.generate(
                        options.addressUpdateRouteName,
                        {'id': address.get('id'), 'entityId': options.entityId}
                    );
                },
                addressMapOptions: {'phone': 'phone'},
                useFormDialog: options.useFormDialog
            });

            addressBook.getCollection().reset(JSON.parse(options.currentAddresses));
        }
    });

    return AccountAddressBook;
});
