/*jslint nomen:true*/
/*global define*/
define(function(require) {
    'use strict';

    var FormComponent;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var _ = require('underscore');
    var widgetManager = require('underscore');

    FormComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            isSaved: false,
            wid: null
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            if (this.options.isSaved) {
                widgetManager.getWidgetInstance(this.options.wid, function(widget) {
                    widget.trigger('formSave');
                });
            }
        }
    });

    return FormComponent;
});
