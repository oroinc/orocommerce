import MultiselectFilter from 'oro/filter/multiselect-filter';

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
    populateDefault: false,

    /**
     * @inheritdoc
     */
    constructor: function SubcategoryFilter(options) {
        SubcategoryFilter.__super__.constructor.call(this, options);
    }
});

export default SubcategoryFilter;
