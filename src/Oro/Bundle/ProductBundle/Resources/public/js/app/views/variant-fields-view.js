define(function(require) {
    'use strict';

    var VariantFieldsView;
    var $ = require('jquery');
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');
    require('jquery-ui');

    VariantFieldsView = BaseView.extend({
        events: {
            'click a.add-list-item': 'reindexValues'
        },

        render: function() {
            this.initSortable();
            this.reindexValues();
            return this;
        },

        reindexValues: function() {
            var index = 1;
            this.$('[name$="[priority]"]').each(function() {
                $(this).val(index++);
            });
        },

        initSortable: function() {
            this.$('.sortable-wrapper').sortable({
                tolerance: 'pointer',
                delay: 100,
                containment: 'parent',
                stop: _.bind(this.reindexValues, this)
            });
        }
    });

    return VariantFieldsView;
});
