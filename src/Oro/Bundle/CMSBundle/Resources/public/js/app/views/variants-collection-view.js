import FieldsGroupsCollectionView from 'oroform/js/app/views/fields-groups-collection-view';
import mediator from 'oroui/js/mediator';
import $ from 'jquery';

const VariantsCollectionView = FieldsGroupsCollectionView.extend({
    PRIMARY_FILED_SELECTOR: '[name$="[default]"]',

    options: {
        form: 'oro_cms_content_block'
    },

    events: {
        'click [name$="[default]"]': 'onPrimaryClick',
        'change >*': 'onChangeInFiledGroup',
        'shown.bs.collapse [data-role="content-variant-item"]': 'scrollToFirstError'
    },

    /**
     * @inheritdoc
     */
    constructor: function VariantsCollectionView(options) {
        VariantsCollectionView.__super__.constructor.call(this, options);
    },

    initialize: function(options) {
        VariantsCollectionView.__super__.initialize.call(this, options);
        this.listenToOnce(mediator, 'page:afterChange', this.validateContainer);
    },

    validateContainer: function() {
        const form = $(`form[name="${this.options.form}"]`);
        const validationErrors = form.find('.validation-failed');
        validationErrors.each(function(key, value) {
            const currentVariantItemContent = $(value.closest('.content-variant-item-content'));
            currentVariantItemContent.attr('data-parent', null);
            currentVariantItemContent.collapse('show');
        });
    },

    scrollToFirstError: function() {
        const form = $(`form[name="${this.options.form}"]`);
        const firstValidationError = form.find('.validation-failed:first');
        if (firstValidationError.length) {
            const scrollableContainer = firstValidationError.closest('.scrollable-container');
            // Small offset from the top border to avoid being overlapped by the top bar
            const offsetTop = 80;
            const scrollTop = firstValidationError.offset().top - scrollableContainer.offset().top - offsetTop;
            scrollableContainer.animate({scrollTop: scrollTop}, scrollTop / 2);
        }
    }
});

export default VariantsCollectionView;
