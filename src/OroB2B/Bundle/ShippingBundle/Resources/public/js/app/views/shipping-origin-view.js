define(function(require) {
    'use strict';

    var ShippingOriginView;
    var $ = require('jquery');
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');

    /**
     * @export orob2bshipping/js/app/views/shipping-origin-view
     * @extends oroui.app.views.base.View
     * @class orob2bshipping.app.views.ShippingOriginView
     */
    ShippingOriginView = BaseView.extend({
        /**
         * @property {Object}
         */
        options: {
            selectors: {
                useSystem: ''
            }
        },

        /**
         * @property {jQuery}
         */
        $useSystem: null,

        /**
         * @property {jQuery}
         */
        $fields: null,

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, options || {});
            this.initLayout().done(_.bind(this.handleLayoutInit, this));
        },

        handleLayoutInit: function() {
            this.$fields = this.$el.find(':input[data-ftid]').filter(':not(' + this.options.selectors.useSystem + ')');

            if (this.options.selectors.useSystem) {
                this.setUseSystem(this.$el.find(this.options.selectors.useSystem));
                this.useSystemChange();
            } else {
                this._setReadOnlyMode(true);
            }
        },

        /**
         * @param {jQuery} $useSystem
         */
        setUseSystem: function($useSystem) {
            this.$useSystem = $useSystem;

            var self = this;
            this.$useSystem.change(function() {
                self.useSystemChange();
            });
        },

        useSystemChange: function() {
            this._setReadOnlyMode(this.$useSystem.is(':checked'));
        },

        /**
         * @param {Boolean} mode
         * @private
         */
        _setReadOnlyMode: function(mode) {
            this.$fields.each(function() {
                $(this).prop('disabled', mode);
            });
        }
    });

    return ShippingOriginView;
});
