define(function(require) {
    'use strict';

    var SubtotalsListenerView;
    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var BaseView = require('oroui/js/app/views/base/view');

    /**
     * @export orob2border/js/app/views/subtotals-listener-view
     * @extends oroui.app.views.base.View
     * @class orob2border.app.views.SubtotalsListenerView
     */
    SubtotalsListenerView = BaseView.extend({
        /**
         * @property {Object}
         */
        options: {
            selectors: {
                fields: ':input',
                filter: ''
            }
        },

        /**
         * @property {jQuery}
         */
        $fields: null,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = $.extend(true, this.options, options || {});

            if ($.isArray(this.options.selectors.fields)) {
                this.options.selectors.fields = this.options.selectors.fields.join(', ');
            }

            this.initLayout().done(_.bind(this.handleLayoutInit, this));
        },

        /**
         * Doing something after loading child components
         */
        handleLayoutInit: function() {
            this.$fields = this._getFields()
                .change(_.bind(this.updateSubtotals, this));
        },

        /**
         * Get fields for listening
         *
         * @returns {jQuery}
         *
         * @private
         */
        _getFields: function() {
            var $fields = this.$el.find(this.options.selectors.fields);

            if (this.options.selectors.filter) {
                $fields = $fields.filter(this.options.selectors.filter);
            }

            return $fields;
        },

        /**
         * Trigger subtotals update
         */
        updateSubtotals: function() {
            mediator.trigger('order-subtotals:update');
        }
    });

    return SubtotalsListenerView;
});
