import BaseView from 'oroui/js/app/views/base/view';
import template from 'tpl-loader!oroorder/templates/line-items-datagrid-presentation-reset-view.html';

const LineItemsDatagridPresentationResetView = BaseView.extend({
    template,

    events: {
        'click [data-role="reset"]': 'onResetClick'
    },

    optionNames: BaseView.prototype.optionNames.concat(['skus', 'autoDispose']),

    skus: '',

    autoDispose: true,

    constructor: function LineItemsDatagridPresentationResetView(...args) {
        LineItemsDatagridPresentationResetView.__super__.constructor.apply(this, args);
    },

    getTemplateData() {
        return {
            ...LineItemsDatagridPresentationResetView.__super__.getTemplateData.call(this),
            skus: this.skus
        };
    },

    onResetClick(event) {
        event.preventDefault();

        this.trigger('reset');

        if (this.autoDispose) {
            this.dispose();
        }
    }
});

export default LineItemsDatagridPresentationResetView;
