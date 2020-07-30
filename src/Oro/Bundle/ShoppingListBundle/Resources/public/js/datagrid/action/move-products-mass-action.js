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

        /**
         * @inheritDoc
         */
        constructor: function MoveProductsMassAction(options) {
            MoveProductsMassAction.__super__.constructor.call(this, options);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            MoveProductsMassAction.__super__.initialize.call(this, options);
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            this.datagrid.off(null, null, this);
            return MoveProductsMassAction.__super__.dispose.call(this);
        },

        /**
         * @inheritDoc
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
         * @inheritDoc
         */
        getActionParameters: function() {
            const params = MoveProductsMassAction.__super__.getActionParameters.call(this);
            params.shopping_list_id = $(this.selectedElement).val();
            return params;
        },

        /**
         * @inheritDoc
         */
        _onAjaxSuccess: function(data, textStatus, jqXHR) {
            MoveProductsMassAction.__super__._onAjaxSuccess.call(this, data, textStatus, jqXHR);

            mediator.trigger('shopping-list:refresh');
        }
    });

    return MoveProductsMassAction;
});
