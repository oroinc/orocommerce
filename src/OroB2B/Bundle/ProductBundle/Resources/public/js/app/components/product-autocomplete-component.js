define(function(require) {
    'use strict';

    var ProductAutocompleteComponent;
    var $ = require('jquery');
    var _ = require('underscore');
    var AutocompleteComponent = require('oro/autocomplete-component');

    ProductAutocompleteComponent = AutocompleteComponent.extend({
        /**
         * @inheritDoc
         */
        initialize: function(options) {
            ProductAutocompleteComponent.__super__.initialize.apply(this, arguments);

            this.$name = $('<div/>').addClass('product-autocomplete-name');
            this.$el.after(this.$name);

            this.$el.change(_.bind(this.change, this));
        },

        change: function() {
            var result = this.resultsMapping[this.$el.val()] || null;
            if (result) {
                this.$el.val(result.sku);
            }
            this.$name.html(result ? result['defaultName.string'] : '');
        }
    });

    return ProductAutocompleteComponent;
});
