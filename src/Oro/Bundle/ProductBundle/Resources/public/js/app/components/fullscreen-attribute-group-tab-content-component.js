import mediator from 'oroui/js/mediator';
import AttributeGroupTabContentComponent from 'oroentityconfig/js/attribute-group-tab-content-component';
import FullscreenPopupView from 'orofrontend/default/js/app/views/fullscreen-popup-view';
import viewportManager from 'oroui/js/viewport-manager';

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

export default FullscreenAttributeGroupTabContentComponent;
