import routing from 'routing';
import BaseView from 'oroui/js/app/views/base/view';
import widgetManager from 'oroui/js/widget-manager';

const ProductKitWidgetView = BaseView.extend({
    autoRender: true,

    optionNames: BaseView.prototype.optionNames.concat(['widgetAlias', 'kitItemId', 'targetId']),

    /**
     * @property {string}
     */
    widgetAlias: null,

    /**
     * @property {string}
     */
    kitItemId: null,

    /**
     * @property {string}
     */
    targetId: null,

    /**
     * @property {boolean}
     */
    loaded: false,

    events: {
        'click [data-toggle="collapse"]': 'onClick',
        'show.bs.collapse': 'onShown',
        'hide.bs.collapse': 'onHidden'
    },

    constructor: function ProductKitWidgetView(...args) {
        ProductKitWidgetView.__super__.constructor.apply(this, args);
    },

    render() {
        this.$('[data-role="kit-item-info-collapsed"]')
            .attr('id', this.targetId);

        ProductKitWidgetView.__super__.render.call(this);
    },

    /**
     * Handling first click on toggler
     *
     * @param {jQuery.Event} event
     * @param {HTMLElement} event.target
     */
    onClick(event) {
        if (this.loaded) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        this.updateWidget();
    },

    /**
     * Hide collapsed view when expanded view appear
     * @param {jQuery.Event} event
     * @param {HTMLElement} event.target
     */
    onShown({target}) {
        if (target.getAttribute('data-role') === 'kit-item-info-expanded') {
            this.$('[data-role="kit-item-info-collapsed"]').collapse('hide');
        }
    },

    /**
     * Hide expanded view when collapsed view appear
     * @param {jQuery.Event} event
     * @param {HTMLElement} event.target
     */
    onHidden({target}) {
        if (target.getAttribute('data-role') === 'kit-item-info-expanded') {
            this.$('[data-role="kit-item-info-collapsed"]').collapse('show');
        }
    },

    /**
     * Update widget view after first expanding
     */
    updateWidget() {
        widgetManager.getWidgetInstanceByAlias(this.widgetAlias, widget => {
            if (widget.loading) {
                widget.loading.abort();
            }

            this.$('[data-toggle="collapse"]').prop('disabled', true);

            widget.setUrl(routing.generate(widget.options.route, {
                id: this.kitItemId,
                state: 'both'
            }));

            widget.render();

            widget.once('renderComplete', () => {
                this.$('[data-role="kit-item-info-expanded"]')
                    .attr('id', this.targetId)
                    .collapse('show');

                this.$('[data-toggle="collapse"]')
                    .prop('disabled', false)
                    .attr('aria-expanded', true)
                    .removeClass('collapsed');
            });

            this.loaded = true;
            this.undelegate('click [data-toggle="collapse"]');
        });
    }
});

export default ProductKitWidgetView;
