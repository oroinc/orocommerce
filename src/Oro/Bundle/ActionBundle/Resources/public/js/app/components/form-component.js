/*jslint nomen:true*/
/*global define*/
define(function(require) {
    'use strict';

    var FormComponent;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var _ = require('underscore');

    FormComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            isSaved: false,
            widgetId: null
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            this.triggerEvents();
        },

        triggerEvents: function() {
            if (this.options.isSaved) {
                var self = this;
                require(['oroui/js/widget-manager'],
                    function(widgetManager) {
                        widgetManager.getWidgetInstance(self.options.widgetId, function(widget) {
                            widget.trigger('formSave');
                        });
                    }
                );
            }
        }
    });

    return FormComponent;
});
