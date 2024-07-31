import FrontendPaginationView from 'orofrontend/js/datagrid/frontend-pagination-view';
import BackendPaginationInput from './backend-pagination-input';
import BackendPaginationStepper from './backend-pagination-stepper';

/**
 * Datagrid pagination variant view for server side rendering
 *
 * @export  oroproduct/js/app/datagrid/pagination-stepper
 * @class   orofrontend.datagrid.BackendPaginationView
 * @extends oroproduct.datagrid.FrontendPaginationView
 */
const BackendPaginationView = FrontendPaginationView.extend({
    themeOptions: {
        optionPrefix: 'backendpagination',
        el: '[data-grid-pagination]'
    },

    constructor: function BackendPaginationView(...args) {
        BackendPaginationView.__super__.constructor.apply(this, args);
    },

    renderPaginator(options) {
        return this.isApplicable() ? new BackendPaginationStepper(options) : new BackendPaginationInput(options);
    }
});

export default BackendPaginationView;
