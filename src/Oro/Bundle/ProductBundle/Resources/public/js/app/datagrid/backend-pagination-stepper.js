import PaginationStepper from 'orofrontend/js/datagrid/pagination-stepper';
import template from 'tpl-loader!oroproduct/templates/datagrid/backend-pagination-stepper.html';

const BackendPaginationStepper = PaginationStepper.extend({
    template,

    constructor: function BackendPaginationStepper(...args) {
        BackendPaginationStepper.__super__.constructor.apply(this, args);
    },

    initialize(options) {
        PaginationStepper.__super__.initialize.call(this, options);

        this.scrollToPosition = this.$el.closest('[data-role="page-main-container"]').position();
    }
});

export default BackendPaginationStepper;
