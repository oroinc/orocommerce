define(function(require) {
    'use strict';

    var ShippingCostView;
    var $ = require('jquery');
    var _ = require('underscore');
    var TotalsListener = require('oropricing/js/app/listener/totals-listener');
    var BaseView = require('oroui/js/app/views/base/view');

    /**
     * @export oroorder/js/app/views/shipping-cost-view
     * @extends oroui.app.views.base.View
     * @class oroorder.app.views.ShippingCostView
     */
    ShippingCostView = BaseView.extend({
        /**
         * @property {Object}
         */
        options: {},

        /**
         * @property {jQuery}
         */
        $form: null,

        /**
         * @property {jQuery}
         */
        $fields: null,

        /**
         * @property {Object}
         */
        fieldsByName: null,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, options || {});
            this.initLayout().done(_.bind(this.handleLayoutInit, this));
        },

        /**
         * Doing something after loading child components
         */
        handleLayoutInit: function() {
            this.$form = this.$el.closest('form');
            this.$fields = this.$el.find(':input[name]');

            this.fieldsByName = {};
            this.$fields.each(_.bind(function(i, field) {
                this.fieldsByName[this.formFieldName(field)] = $(field);
            }, this));

            this.subtotalFields(this.fieldsByName);
        },

        /**
         * @param {Object} field
         * @returns {String}
         */
        formFieldName: function(field) {
            var name = '';
            var nameParts = field.name.replace(/.*\[[0-9]+\]/, '').replace(/[\[\]]/g, '_').split('_');
            var namePart;

            for (var i = 0, iMax = nameParts.length; i < iMax; i++) {
                namePart = nameParts[i];
                if (!namePart.length) {
                    continue;
                }
                if (name.length === 0) {
                    name += namePart;
                } else {
                    name += namePart[0].toUpperCase() + namePart.substr(1);
                }
            }
            return name;
        },

        /**
         * @param {jQuery|Array} $fields
         */
        subtotalFields: function($fields) {
            TotalsListener.listen($fields);
        }
    });

    return ShippingCostView;
});
