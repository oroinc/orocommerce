import mediator from 'oroui/js/mediator';
import BasePlugin from 'oroui/js/app/plugins/base/plugin';
import tools from 'oroui/js/tools';
import LineItemsDatagridPresentationResetView from 'oroorder/js/app/views/line-items-datagrid-presentation-reset-view';

const LineItemsDatagridPresentationPlugin = BasePlugin.extend({
    constructor: function LineItemsDatagridPresentationPlugin(...args) {
        this.prevState = {};
        LineItemsDatagridPresentationPlugin.__super__.constructor.apply(this, args);
    },

    enable() {
        LineItemsDatagridPresentationPlugin.__super__.enable.call(this);

        this.listenTo(mediator,
            `line-items-datagrid-presentation:${this.main.collection.inputName}:apply`,
            this.onApplyPresentation
        );
    },

    onApplyPresentation(filters) {
        if (filters === void 0) {
            return console.error('Filters are required to apply datagrid presentation');
        }

        if (!this.main.filterManager) {
            return;
        }

        if (!this.main.collection.state.presentationMode) {
            this.prevState = tools.deepClone(this.main.collection.state);
        }

        const routerEnabled = this.main.routerEnabled;
        this.main.routerEnabled = false;

        this.main.collection.updateState({
            filters,
            presentationMode: true
        });

        this.main.collection.fetch({
            reset: true
        });

        this.main.filterManager.$el.hide();
        this.main.$el.siblings('.grid-views').hide();
        Object.values(this.main.toolbars).forEach(toolbar => toolbar.$el.hide());

        if (this._resetView) {
            this._resetView.dispose();
        }

        this.listenToOnce(this.main.collection, 'reset', () => {
            const skus = this.main.collection.map(model => model.get('productSku')).join(', ');

            this._resetView = new LineItemsDatagridPresentationResetView({
                autoRender: true,
                container: this.main.$el,
                containerMethod: 'prepend',
                skus
            });

            tools.elementScrollIntoViewIfNeeded(this._resetView.el);

            this.listenToOnce(this._resetView, 'reset', () => {
                this.main.collection.updateState(this.prevState);
                this.main.collection.fetch({
                    reset: true
                });

                this.main.routerEnabled = routerEnabled;

                this.main.filterManager.$el.show();
                this.main.$el.siblings('.grid-views').show();
                Object.values(this.main.toolbars).forEach(toolbar => toolbar.$el.show());
            });
        });
    }
});

export default LineItemsDatagridPresentationPlugin;
