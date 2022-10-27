import ViewComponent from 'oroui/js/app/components/view-component';

const QuickAddRowExtensionViewComponent = ViewComponent.extend({
    relatedSiblingComponents: {
        quickAddRowComponent: 'quick-add-row-component'
    },

    constructor: function QuickAddRowExtensionViewComponent(options) {
        QuickAddRowExtensionViewComponent.__super__.constructor.call(this, options);
    },

    _initializeView(options, View) {
        const {relatedSiblingComponents, productsCollection, ...opts} = options;

        if (this.disposed || relatedSiblingComponents.quickAddRowComponent.disposed) {
            this._resolveDeferredInit();
            return;
        }

        // Takes model from quickAddRowView instance and pass to a RowExtensionView within options
        opts.model = relatedSiblingComponents.quickAddRowComponent.view.model;
        QuickAddRowExtensionViewComponent.__super__._initializeView.call(this, opts, View);
    }
});

export default QuickAddRowExtensionViewComponent;
