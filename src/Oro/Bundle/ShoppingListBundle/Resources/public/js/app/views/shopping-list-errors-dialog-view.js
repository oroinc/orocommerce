import BaseView from 'oroui/js/app/views/base/view';
import mediator from 'oroui/js/mediator';
import ShoppingListErrorsModalWidget from 'oro/shopping-list-errors-modal-widget';
import routing from 'routing';

const ShoppingListErrorsDialogView = BaseView.extend({
    optionNames: BaseView.prototype.optionNames.concat([
        'invalidIds', 'dialogTitle', 'route', 'routeParams',
        'triggeredBy', 'action', 'refreshGrids', 'gridName',
        'errorValidationMessages', 'successValidationMessages', 'successProceedBtnLabel'
    ]),

    attributes: {
        type: 'button'
    },

    events() {
        const events = {};

        if (this.invalidIds.length) {
            events['click'] = 'createDialog';
        }

        return events;
    },

    /**
     * @inheritdoc
     */
    constructor: function ShoppingListErrorsDialogView(options) {
        ShoppingListErrorsDialogView.__super__.constructor.call(this, options);
    },

    getUrl() {
        return routing.generate(this.route, this.routeParams);
    },

    getDialogOptions() {
        return {
            autoRender: true,
            method: 'POST',
            url: this.getUrl(),
            title: this.dialogTitle,
            gridName: this.gridName,
            errorValidationMessages: this.errorValidationMessages,
            successValidationMessages: this.successValidationMessages,
            successProceedBtnLabel: this.successProceedBtnLabel,
            widgetData: {
                triggered_by: this.triggeredBy,
                action: this.action,
                invalidIds: this.invalidIds,
                render: true
            }
        };
    },

    createDialog() {
        this.subview('dialog', new ShoppingListErrorsModalWidget(this.getDialogOptions()));

        this.listenTo(this.subview('dialog'), 'close dispose', () => {
            if (this.refreshGrids) {
                if (Array.isArray(this.refreshGrids)) {
                    this.refreshGrids.forEach(gridName => mediator.trigger(`datagrid:doRefresh:${gridName}`));
                } else if (typeof this.refreshGrids === 'string') {
                    mediator.trigger(`datagrid:doRefresh:${this.refreshGrids}`);
                }
            }
        });
    }
});

export default ShoppingListErrorsDialogView;
