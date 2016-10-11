/** @lends TitleView */
define(function(require) {
    'use strict';
    var BaseView = require('oroui/js/app/views/base/view');
    var _ = require('underscore');

    /**
     * Title view, able to handle title rendering.
     *
     * Usage sample:
     * ```javascript
     * var titleView = new TitleView({
     *     model: new Backbone.Model({
     *         title: "Title text"
     *     }),
     *     fieldName: 'title',
     *     autoRender: true
     * });
     * ```
     *
     * @class
     * @augments BaseView
     * @exports TitleView
     */
    var TitleView = BaseView.extend(/** @exports TitleView.prototype */{
        showDefault: true,
        template: require('tpl!orofrontend/templates/viewer/title-view.html'),

        listen: {
            'change model': 'render'
        },

        initialize: function(options) {
            this.fieldName = _.result(options, 'fieldName', 'value');
            this.tooltip = _.result(options, 'tooltip', null);
            this.additionalClass = _.result(options, 'additionalClass', '');
            return TitleView.__super__.initialize.apply(this, arguments);
        },

        getTemplateData: function() {
            return {
                value: _.escape(this.model.get(this.fieldName)),
                tooltip: this.tooltip,
                additionalClass: this.additionalClass
            };
        }
    });

    return TitleView;
});
