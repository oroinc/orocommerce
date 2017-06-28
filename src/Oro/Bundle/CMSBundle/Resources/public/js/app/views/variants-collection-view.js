define(function(require) {
    'use strict';

    var VariantsCollectionView;
    var FieldsGroupsCollectionView = require('oroform/js/app/views/fields-groups-collection-view');

    VariantsCollectionView = FieldsGroupsCollectionView.extend({
        PRIMARY_FILED_SELECTOR: '[name$="[default]"]',

        events: {
            'click [name$="[default]"]': 'onPrimaryClick',
            'change >*': 'onChangeInFiledGroup'
        }
    });

    return VariantsCollectionView;
});
