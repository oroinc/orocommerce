define(function(require) {
    'use strict';

    var SubcategoryFilter;
    var template = require('tpl!orocatalog/templates/filter/subcategory-filter.html');
    var MultiselectFilter = require('oro/filter/multiselect-filter');

    SubcategoryFilter = MultiselectFilter.extend({
        /**
         * @inheritDoc
         */
        emptyValue: {
            value: []
        },

        /**
         * @inheritDoc
         */
        template: template,

        /**
         * @inheritDoc
         */
        populateDefault: false,

        /**
         * @inheritDoc
         */
        constructor: function SubcategoryFilter() {
            SubcategoryFilter.__super__.constructor.apply(this, arguments);
        }
    });

    return SubcategoryFilter;
});
