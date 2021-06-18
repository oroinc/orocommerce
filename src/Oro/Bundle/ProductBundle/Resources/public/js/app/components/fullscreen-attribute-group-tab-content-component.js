define(function(require) {
    'use strict';

    const AttributeGroupTabContentComponent = require('oroentityconfig/js/attribute-group-tab-content-component');
    const FullscreenPopupView = require('orofrontend/blank/js/app/views/fullscreen-popup-view');
    const viewportManager = require('oroui/js/viewport-manager');

    const FullscreenAttributeGroupTabContentComponent = AttributeGroupTabContentComponent.extend({
        /**
         * @inheritdoc
         */
        constructor: function FullscreenAttributeGroupTabContentComponent(options) {
            FullscreenAttributeGroupTabContentComponent.__super__.constructor.call(this, options);
        },

        /**
         * @param {Object} options
         */
        initialize: function(options) {
            this.viewport = options.viewport || {};
            return FullscreenAttributeGroupTabContentComponent.__super__.initialize.call(this, options);
        },

        onGroupChange: function(model, initialize) {
            FullscreenAttributeGroupTabContentComponent.__super__.onGroupChange.call(this, model);

            if (initialize || this.id !== model.get('id') || !viewportManager.isApplicable(this.viewport)) {
                return;
            }

            if (!model.get('active')) {
                if (this.fullscreenView) {
                    this.fullscreenView.dispose();
                    delete this.fullscreenView;
                }
            } else if (!this.fullscreenView) {
                this.fullscreenView = new FullscreenPopupView({
                    disposeOnClose: true,
                    contentElement: this.el
                });
                this.fullscreenView.on('close', function() {
                    this.fullscreenView.dispose();
                    delete this.fullscreenView;
                }, this);

                this.fullscreenView.show();
            }
        }
    });

    return FullscreenAttributeGroupTabContentComponent;
});
