define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const BaseView = require('oroui/js/app/views/base/view');
    const ElementsHelper = require('orofrontend/js/app/elements-helper');

    /**
     * @export ororfp/js/app/views/line-items-view
     * @extends oroui.app.views.base.View
     * @class ororfp.app.views.LineItemsView
     */
    const LineItemsView = BaseView.extend(_.extend({}, ElementsHelper, {
        constructor: function LineItemsView(options) {
            LineItemsView.__super__.constructor.call(this, options);
        },

        /**
         * @param {Object} options
         */
        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, options || {});

            LineItemsView.__super__.initialize.call(this, options);

            this.initializeSubviews();
        }
    }));

    return LineItemsView;
});
