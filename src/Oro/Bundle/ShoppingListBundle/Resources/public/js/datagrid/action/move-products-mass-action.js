define(function(require) {
    'use strict';

    const MassAction = require('oro/datagrid/action/mass-action');
    const loadModules = require('oroui/js/app/services/load-modules');
    const mediator = require('oroui/js/mediator');
    const $ = require('jquery');

    /**
     * Move products to another shopping list
     *
     * @export  oro/datagrid/action/move-products-mass-action
     * @class   oro.datagrid.action.MoveProductsMassAction
     * @extends oro.datagrid.action.MassAction
     */
    const MoveProductsMassAction = MassAction.extend({
        /**
         * @property {String}
         */
        selectedElement: null,

        reloadData: false,

        /**
         * @inheritdoc
         */
        constructor: function MoveProductsMassAction(options) {
            MoveProductsMassAction.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            MoveProductsMassAction.__super__.initialize.call(this, options);

            this.route_parameters.id = this.route_parameters[this.datagrid.name]['shopping_list_id'];
        },

        /**
         * @inheritdoc
         */
        dispose: function() {
            this.datagrid.off(null, null, this);
            return MoveProductsMassAction.__super__.dispose.call(this);
        },

        /**
         * @inheritdoc
         */
        _handleWidget: function() {
            if (this.dispatched) {
                return;
            }
            this.frontend_options = this.frontend_options || {};
            this.frontend_options.url = this.getLinkWithParameters();
            this.frontend_options.title = this.frontend_options.title || this.label;

            loadModules(
                'orofrontend/js/app/components/frontend-' + this.frontend_handle + '-widget',
                function(WidgetType) {
                    const widget = new WidgetType(this.frontend_options);
                    widget.render();

                    this.listenToOnce(widget, {
                        'frontend-dialog:accept': this._handleAjax.bind(this)
                    });
                }.bind(this)
            );
        },

        /**
         * @inheritdoc
         */
        getActionParameters: function() {
            const params = MoveProductsMassAction.__super__.getActionParameters.call(this);
            params.shopping_list_id = $(this.selectedElement).val();
            return params;
        },

        /**
         * @inheritdoc
         */
        _onAjaxSuccess: function(data) {
            this.datagrid.collection.fetch({
                reset: true,
                toggleLoading: false
            });
            this.datagrid.resetSelectionState();

            data.successMessageOptions = {namespace: 'shopping_list'};

            this._showAjaxSuccessMessage(data);
            mediator.trigger('shopping-list:refresh');
        },

        /**
         * @inheritdoc
         * @private
         */
        _doAjaxRequest() {
            const {values} = this.getActionParameters();
            if (values.length) {
                values.split(',').forEach(value => {
                    const model = this.datagrid.collection.get(value);
                    model.toggleLoadingOverlay(true);
                });
            } else {
                this.datagrid.collection.models.forEach(model => {
                    model.toggleLoadingOverlay(true);
                });
            }

            MoveProductsMassAction.__super__._doAjaxRequest.call(this);
        }
    });

    return MoveProductsMassAction;
});
