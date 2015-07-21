/*jslint nomen:true*/
/*global define*/
define(function(require) {
    'use strict';

    var CustomerAddressBook,
        BaseComponent = require('oroui/js/app/components/base/component'),
        _ = require('underscore'),
        routing = require('routing'),
        AddressBook = require('oroaddress/js/address-book'),
        widgetManager = require('oroui/js/widget-manager');

    CustomerAddressBook = BaseComponent.extend({
        initialize: function(options) {
            widgetManager.getWidgetInstance(options.wid, function(widget) {
                /** @type oroaddress.AddressBook */
                var addressBook = new AddressBook({
                    el: '#address-book',
                    addressListUrl: options.addressListUrl,
                    addressCreateUrl: options.addressCreateUrl,
                    addressUpdateUrl: function() {
                        var address = arguments[0];
                        return routing.generate(
                            options.addressUpdateRouteName,
                            _.extend({}, options.addressUpdateParams, {'id': address.get('id')})
                        );
                    }
                });
                widget.getAction('add_address', 'adopted', function(action) {
                    action.on('click', _.bind(addressBook.createAddress, addressBook));
                });
                addressBook
                    .getCollection()
                    .reset(JSON.parse(options.currentAddresses));
            });
        }
    });

    return CustomerAddressBook;
});
