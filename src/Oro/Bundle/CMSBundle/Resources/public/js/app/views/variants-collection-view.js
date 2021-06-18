define(function(require) {
    'use strict';

    const FieldsGroupsCollectionView = require('oroform/js/app/views/fields-groups-collection-view');

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
        }
    });

    return VariantsCollectionView;
});
