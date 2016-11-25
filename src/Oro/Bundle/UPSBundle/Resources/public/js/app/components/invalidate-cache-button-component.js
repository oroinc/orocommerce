/*jslint nomen:true*/
/*global define*/
define(function(require) {
    'use strict';

    var UPSInvalidateCacheComponent;
    var $ = require('jquery');
    var _ = require('underscore');
    var BaseComponent = require('oroui/js/app/components/base/component');

    UPSInvalidateCacheComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            submitButton: '.invalidate-cache-form #invalidate_cache_submit_button',
            invalidateNow: '.invalidate-cache-form>[name="oro_action_operation[invalidateNow]"]'
        },

        /**
         * @property {jQuery.Element}
         */
        submitButton: null,


        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            this.submitButton = $(this.options.submitButton);
            this.submitButton.on('click', _.bind(this.onSubmitClick, this));
        },

        onSubmitClick: function() {
            $(this.options.invalidateNow).val(1);
            return true;
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.submitButton.off('change');

            UPSInvalidateCacheComponent.__super__.dispose.call(this);
        }
    });

    return UPSInvalidateCacheComponent;
});
