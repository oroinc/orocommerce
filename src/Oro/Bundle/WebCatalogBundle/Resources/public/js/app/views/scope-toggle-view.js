define(function(require) {
    'use strict';

    var ContentNodeParentScopeView;
    var $ = require('jquery');
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');

    /**
     * @export orowebcatalog/js/app/views/scope-toggle-view
     * @extends oroui.app.views.base.View
     * @class orowebcatalog.app.views.ContentNodeParentScopeView
     */
    ContentNodeParentScopeView = BaseView.extend({
        /**
         * @property {Object}
         */
        options: {
            selectors: {
                useParentScopeSelector: '.parent-scope-use',
                scopesSelector: '.scopes'
            }
        },

        /**
         * @property {jQuery}
         */
        $useParentScope: null,

        /**
         * @property {jQuery}
         */
        $scopeFields: null,

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, options || {});
            this.initLayout().done(_.bind(this.handleLayoutInit, this));
        },

        handleLayoutInit: function() {
            this.$useParentScope = this.$el.find(this.options.selectors.useParentScopeSelector);
            this.$scopeFields = this.$el.find(this.options.selectors.scopesSelector);

            this._initScopes();
            this.$useParentScope.on('change',_.bind(this._toggleScopes, this));
        },

        _initScopes: function() {
            if (this.$useParentScope.is(':checked')) {
                this.$scopeFields.hide();
            } else {
                this.$scopeFields.show();
            }
        },

        _toggleScopes: function(event) {
            this.$scopeFields.toggle(!event.target.checked);
        }
    });

    return ContentNodeParentScopeView;
});
