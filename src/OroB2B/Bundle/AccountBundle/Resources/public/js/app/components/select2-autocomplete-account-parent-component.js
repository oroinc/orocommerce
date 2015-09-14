define(function(require) {
    'use strict';

    var Select2AutocompleteAccountParentComponent;
    var _ = require('underscore');
    var Select2AutocompleteComponent = require('oro/select2-autocomplete-component');

    Select2AutocompleteAccountParentComponent = Select2AutocompleteComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            delimiter: ';'
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            Select2AutocompleteAccountParentComponent.__super__.initialize.call(this, options);
        },

        /**
         * @inheritDoc
         */
        makeQuery: function(query) {
            return [query, this.options.configs.accountId].join(this.options.delimiter);
        }
    });

    return Select2AutocompleteAccountParentComponent;
});
