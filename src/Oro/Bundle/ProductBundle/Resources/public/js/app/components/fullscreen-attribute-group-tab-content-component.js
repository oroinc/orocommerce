define(function(require) {
    'use strict';

    const mediator = require('oroui/js/mediator');
    const AttributeGroupTabContentComponent = require('oroentityconfig/js/attribute-group-tab-content-component');
    const FullscreenPopupView = require('orofrontend/default/js/app/views/fullscreen-popup-view');
    const viewportManager = require('oroui/js/viewport-manager').default;

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
            FullscreenAttributeGroupTabContentComponent.__super__.initialize.call(this, options);
            this.viewport = options.viewport || {};
            this.listenTo(mediator, 'entity-config:attribute-group:click', this.onGroupClick);
        },

        onGroupClick: function(model, initialize) {
            if (initialize || this.id !== model.get('id') || !viewportManager.isApplicable(this.viewport)) {
                return;
            }

            if (!this.fullscreenView) {
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
