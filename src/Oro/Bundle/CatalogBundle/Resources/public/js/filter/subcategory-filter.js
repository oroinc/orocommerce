define(function(require) {
    'use strict';

    var SubcategoryFilter;
    var _ = require('underscore');
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

        updateVisibility: function() {
            this.visible = !_.isEmpty(this.counts);
        }
    });

    return SubcategoryFilter;
});
