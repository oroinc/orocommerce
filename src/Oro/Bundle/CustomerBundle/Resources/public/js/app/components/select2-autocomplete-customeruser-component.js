/*global define*/
define(function(require) {
    'use strict';

    var Select2AutocompleteCustomerUserComponent;
    var $ = require('jquery');
    var _ = require('underscore');
    var Select2AutocompleteComponent = require('oro/select2-autocomplete-component');

    Select2AutocompleteCustomerUserComponent = Select2AutocompleteComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            customerSelect: '.customer-customer-select input[type="hidden"]',
            delimiter: ';'
        },

        /**
         * @property {Object}
         */
        $customerSelect: null,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            Select2AutocompleteCustomerUserComponent.__super__.initialize.call(this, options);

            this.$customerSelect = $(this.options.customerSelect);
        },

        /**
         * @inheritDoc
         */
        makeQuery: function(query) {
            return [query, this.$customerSelect.val()].join(this.options.delimiter);
        }
    });

    return Select2AutocompleteCustomerUserComponent;
});
