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
            'useFormDialog': false,
            'template': ''
        },

        /**
         * @param {Object} options
         */
        initialize: function(options) {
            options = _.defaults(options || {}, this.defaultOptions);

            /** @type oroaddress.AddressBook */
            var addressBook = new AddressBook({
                el: options._sourceElement.get(0),
                template: options.template,
                addressListUrl: options.addressListUrl,
                addressCreateUrl: options.addressCreateUrl,
                addressUpdateUrl: function() {
                    var address = arguments[0];
                    return routing.generate(
                        options.addressUpdateRouteName,
                        {'id': address.get('id'), 'entityId': options.entityId}
                    );
                },
                allowToRemovePrimary: true,
                addressMapOptions: {'phone': 'phone'},
                useFormDialog: options.useFormDialog
            });

            addressBook.getCollection().reset(JSON.parse(options.currentAddresses));
            options._sourceElement.children('.view-loading').remove();
        }
    });

    return AccountAddressBook;
});
