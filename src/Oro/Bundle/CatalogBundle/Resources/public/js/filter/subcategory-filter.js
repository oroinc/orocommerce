define(function(require) {
    'use strict';

    const template = require('tpl-loader!orocatalog/templates/filter/subcategory-filter.html');
    const MultiselectFilter = require('oro/filter/multiselect-filter');

    const SubcategoryFilter = MultiselectFilter.extend({
        /**
         * @inheritdoc
         */
        emptyValue: {
            value: []
        },

        /**
         * @inheritdoc
         */
        template: template,

        /**
         * @inheritdoc
         */
        populateDefault: false,

        /**
         * @inheritdoc
         */
        constructor: function SubcategoryFilter(options) {
            SubcategoryFilter.__super__.constructor.call(this, options);
        }
    });

    return SubcategoryFilter;
});
