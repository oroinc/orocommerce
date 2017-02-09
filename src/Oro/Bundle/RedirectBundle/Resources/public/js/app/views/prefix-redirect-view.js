define(function(require) {
    'use strict';

    var PrefixRedirect;
    var $ = require('jquery');
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');

    /**
     * @export orowebcatalog/js/app/views/scope-toggle-view
     * @extends oroui.app.views.base.View
     * @class orowebcatalog.app.views.PrefixRedirect
     */
    PrefixRedirect = BaseView.extend({
        /**
         * @property {Object}
         */
        options: {
            selectors: {
                prefixSelector: '.slug-prefix',
                redirectSelector: '.create-redirect'
            }
        },

        /**
         * @property {jQuery}
         */
        $prefixField: null,

        /**
         * @property string
         */
        defaultPrefixValue: '',

        /**
         * @property {jQuery}
         */
        $createRedirectField: null,

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, options || {});

            this.$prefixField = $(this.el).find(this.options.selectors.prefixSelector);
            this.defaultPrefixValue = this.$prefixField.val();
            this.$createRedirectField = $(this.el).find(this.options.selectors.redirectSelector);

            this.$prefixField.on('keyup', _.bind(this.onPrefixChange, this));
        },

        onPrefixChange: function(event) {
            if ($(event.target).val() === this.defaultPrefixValue) {
                this.$createRedirectField.hide();
            } else {
                this.$createRedirectField.show();
            }
        }
    });

    return PrefixRedirect;
});
