/*jslint nomen:true*/
/*global define*/
define([
    'underscore',
    'oroui/js/messenger',
    'orotranslation/js/translator',
    'oro/datagrid/action/mass-action',
    'oroui/js/mediator'
], function (_, messenger, __, MassAction, mediator) {
    'use strict';

    var AddProductsAction;

    /**
     * Mark email as read/unread
     *
     * @export  oro/datagrid/action/add-products-mass-action
     * @class   oro.datagrid.action.MarkAction
     * @extends oro.datagrid.action.MassAction
     */
    AddProductsAction = MassAction.extend({
        initialize: function (options) {
            AddProductsAction.__super__.initialize.apply(this, arguments);
            mediator.on('frontend:shoppinglist:products-add', this._beforeProductsAdd, this);
        },
        /**
         * @param {object} eventArgs
         */
        _beforeProductsAdd: function (eventArgs) {
            this.route_parameters['shoppingList'] = eventArgs.id;
            this.run(true);
        },
        /**
         * Overridden in order to set shoppingList route param
         *
         * @param {boolean} isCustom
         */
        run: function (isCustom) {
            if (!isCustom) {
                this.route_parameters['shoppingList'] = 'current';
            }
            AddProductsAction.__super__.run.apply(this);
        }
    });

    return AddProductsAction;
});
