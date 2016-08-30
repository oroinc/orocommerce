/** @lends TextView */
define(function(require) {
    'use strict';
    var BaseView = require('oroui/js/app/views/base/view');
    var _ = require('underscore');

    /**
     * Text view, able to handle title rendering.
     *
     * Usage sample:
     * ```javascript
     * var textView = new TextView({
     *     model: new Backbone.Model({
     *         note: "Some text"
     *     }),
     *     fieldName: 'note',
     *     autoRender: true
     * });
     * ```
     *
     * @class
     * @augments BaseView
     * @exports TextView
     */
    var TextView = BaseView.extend(/** @exports TextView.prototype */{
        showDefault: true,
        template: require('tpl!orofrontend/templates/viewer/text-view.html'),

        listen: {
            'change model': 'render'
        },

        initialize: function(options) {
            this.fieldName = _.result(options, 'fieldName', 'value');
            return TextView.__super__.initialize.apply(this, arguments);
        },

        getTemplateData: function() {
            return {
                value: this.model.get(this.fieldName)
            };
        }
    });

    return TextView;
});
