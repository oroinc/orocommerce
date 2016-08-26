/** @lends ClearFieldData */
define(function(require) {
    'use strict';

    var ClearFieldData;
    var $ = require('jquery');
    var _ = require('underscore');
    var BaseComponent = require('oroui/js/app/components/base/component');

    ClearFieldData = BaseComponent.extend(/** @exports ClearFieldData.prototype */ {
        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.$el = $(options._sourceElement);
            var triggerSelector = this.$el.data('trigger-selector') || '#trigger';
            this.$trigger = this.$el.find(triggerSelector);
            this.clear();
            this.$trigger.on('change', _.bind(this.clear, this));
        },
        clear: function() {
            if (this.$trigger.attr('checked') !== 'checked') {
                this.$el.find('input[type=text], input[type=date], textarea').val('');
            }
        }
    });

    return ClearFieldData;
});
