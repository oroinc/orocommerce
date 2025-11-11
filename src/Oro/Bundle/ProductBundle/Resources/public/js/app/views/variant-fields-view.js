import $ from 'jquery';
import BaseView from 'oroui/js/app/views/base/view';
import 'jquery-ui/widgets/sortable';

const VariantFieldsView = BaseView.extend({
    events: {
        'click a.add-list-item': 'reindexValues'
    },

    /**
     * @inheritdoc
     */
    constructor: function VariantFieldsView(options) {
        VariantFieldsView.__super__.constructor.call(this, options);
    },

    render: function() {
        this.initSortable();
        this.reindexValues();
        return this;
    },

    reindexValues: function() {
        let index = 1;
        this.$('[name$="[priority]"]').each(function() {
            $(this).val(index++);
        });
    },

    initSortable: function() {
        this.$('[data-name="field__variant-fields"]').sortable({
            handle: '[data-name="sortable-handle"]',
            tolerance: 'pointer',
            delay: 100,
            containment: 'parent',
            stop: this.reindexValues.bind(this)
        });
    }
});

export default VariantFieldsView;
