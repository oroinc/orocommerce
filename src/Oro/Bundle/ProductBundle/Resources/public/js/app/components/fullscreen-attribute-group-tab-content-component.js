define(function(require) {
    'use strict';

    var FullscreenAttributeGroupTabContentComponent;
    var AttributeGroupTabContentComponent = require('oroentityconfig/js/attribute-group-tab-content-component');
    var FullscreenPopupView = require('orofrontend/blank/js/app/views/fullscreen-popup-view');
    var viewportManager = require('oroui/js/viewport-manager');

    FullscreenAttributeGroupTabContentComponent = AttributeGroupTabContentComponent.extend({
        /**
         * @param {Object} options
         */
        initialize: function(options) {
            this.viewport = options.viewport || {};
            return FullscreenAttributeGroupTabContentComponent.__super__.initialize.call(this, options);
        },

        onGroupChange: function(model) {
            if (!viewportManager.isApplicable(this.viewport)) {
                return FullscreenAttributeGroupTabContentComponent.__super__.onGroupChange.call(this, model);
            }

            if (this.id !== model.id || model.isActiveChanged) {
                return;
            }

            if (this.fullscreenView) {
                this.fullscreenView.dispose();
                delete this.fullscreenView;
            }

            this.fullscreenView = new FullscreenPopupView({
                contentSelector: '[data-attribute-content="' + this.id + '"]'
            });
            this.fullscreenView.show();

            FullscreenAttributeGroupTabContentComponent.__super__.onGroupChange.call(this, model);
        }
    });

    return FullscreenAttributeGroupTabContentComponent;
});
