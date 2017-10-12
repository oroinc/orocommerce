define(function(require) {
    'use strict';

    var SubcategoryFilter;
    var _ = require('underscore');
    var template = require('tpl!orocatalog/templates/filter/subcategory-filter.html');
    var MultiselectFilter = require('oro/filter/multiselect-filter');

    SubcategoryFilter = MultiselectFilter.extend({
        /**
         * @property {Object}
         */
        counts: [],

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
        templateSelector: '#subcategory-filter-template',

        /**
         * @inheritDoc
         */
        populateDefault: false,

        /**
         * @inheritDoc
         */
        optionNames: MultiselectFilter.prototype.optionNames.concat(['counts']),

        /**
         * @inheritDoc
         */
        getTemplateData: function() {
            var data = SubcategoryFilter.__super__.getTemplateData.apply(this, arguments);

            _.map(data.options, function(category) {
                category.count = this.counts[category.value] || 0;
            }, this);

            data.options = _.filter(data.options, function(category) {
                return category.count > 0;
            });

            return data;
        }
    });

    return SubcategoryFilter;
});
