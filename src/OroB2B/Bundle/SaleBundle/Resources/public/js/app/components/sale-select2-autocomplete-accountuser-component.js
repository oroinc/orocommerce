/*global define*/
define(function(require) {
    'use strict';

    var Select2AutocompleteAccountUserComponent;
    var $ = require('jquery');
    var _ = require('underscore');
    var Select2AutocompleteComponent = require('oro/select2-autocomplete-component');

    Select2AutocompleteAccountUserComponent = Select2AutocompleteComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            accountSelect: '.sale-quote-account-select input[type="hidden"]',
            defaultDelimiter: ';'
        },

        /**
         * @property {Object}
         */
        $accountSelect: null,

        /**
         * @property {String}
         */
        delimiter: null,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            Select2AutocompleteAccountUserComponent.__super__.initialize.call(this, options);
            this.$accountSelect = $(this.options.accountSelect);
            this.delimiter = options.configs.delimiter || this.options.defaultDelimiter;
        },

        /**
         * @inheritDoc
         */
        makeQuery: function(query) {
            return [this.$accountSelect.val(), query].join(this.delimiter);
        }
    });

    return Select2AutocompleteAccountUserComponent;
});
