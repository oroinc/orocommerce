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
        counts: {},

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
        listen: {
            'metadata-loaded': 'onMetadataLoaded'
        },


        /**
         * @inheritDoc
         */
        constructor: function SubcategoryFilter() {
            SubcategoryFilter.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function() {
            SubcategoryFilter.__super__.initialize.apply(this, arguments);

            this.updateVisibility();
        },

        /**
         * @param {Object} metadata
         */
        onMetadataLoaded: function(metadata) {
            this.counts = metadata.counts || {};

            this.updateVisibility();

            if (this.isRendered()) {
                this.render();
            }
        },

        updateVisibility: function() {
            this.visible = !_.isEmpty(this.counts);
        },

        /**
         * @inheritDoc
         */
        getTemplateData: function() {
            var data = SubcategoryFilter.__super__.getTemplateData.apply(this, arguments);

            data.options = _.filter(data.options, function(category) {
                category.count = this.counts[category.value] || 0;

                return category.count > 0;
            }, this);

            return data;
        }
    });

    return SubcategoryFilter;
});
