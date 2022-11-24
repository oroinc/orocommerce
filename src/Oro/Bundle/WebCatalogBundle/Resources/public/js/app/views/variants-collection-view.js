define(function(require) {
    'use strict';

    const FieldsGroupsCollectionView = require('oroform/js/app/views/fields-groups-collection-view');
    const mediator = require('oroui/js/mediator');

    const VariantsCollectionView = FieldsGroupsCollectionView.extend({
        PRIMARY_FILED_SELECTOR: '[name$="[default]"]',

        events: {
            'click [name$="[default]"]': 'onPrimaryClick',
            'change >*': 'onChangeInFiledGroup'
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
            const $validationField = this.$el.find('[data-name="collection-validation"]:first');
            const $form = $validationField.closest('form');
            const $firstValidationError = $form.find('.validation-failed');

            if ($firstValidationError.length) {
                const $scrollableContainer = $firstValidationError.closest('.scrollable-container');
                // Small offset from the top border to avoid being overlapped by the top bar
                const offsetTop = 32;
                const scrollTop = $firstValidationError.offset().top - $scrollableContainer.offset().top - offsetTop;

                $scrollableContainer.animate({
                    scrollTop: scrollTop
                }, scrollTop / 2);
            }
        }
    });

    return VariantsCollectionView;
});
