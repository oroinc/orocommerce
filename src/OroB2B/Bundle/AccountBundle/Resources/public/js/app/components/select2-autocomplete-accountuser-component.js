/*global define*/
define(function(require) {
    'use strict';

    var Select2AutocompleteAccountUserComponent;
    var $ = require('jquery');
    var Select2AutocompleteComponent = require('oro/select2-autocomplete-component');

    Select2AutocompleteAccountUserComponent = Select2AutocompleteComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            accountSelect: '.account-account-select input[type="hidden"]',
            delimiter: ';'
        },

        /**
         * @property {Object}
         */
        $accountSelect: null,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            Select2AutocompleteAccountUserComponent.__super__.initialize.call(this, options);

            this.$accountSelect = $(this.options.accountSelect);
        },

        /**
         * @inheritDoc
         */
        makeQuery: function(query) {
            return [this.$accountSelect.val(), query].join(this.options.delimiter);
        }
    });

    return Select2AutocompleteAccountUserComponent;
});
