import _ from 'underscore';
import __ from 'orotranslation/js/translator';
import routing from 'routing';
import mediator from 'oroui/js/mediator';
import ModelAction from 'oro/datagrid/action/model-action';
import ShoppinglistAddNotesModalView from '../../app/views/shoppinglist-add-notes-modal-view';

/**
 * Add notes action, triggers REST PATCH request
 *
 * @export  oro/datagrid/action/add-notes-action
 * @class   oro.datagrid.action.AddNotesAction
 * @extends oro.datagrid.action.ModelAction
 */
const AddNotesAction = ModelAction.extend({
    /**
     * @property {Object}
     */
    formWidget: null,

    requestType: 'PATCH',

    reloadData: false,

    /**
     * @inheritdoc
     */
    constructor: function AddNotesAction(options) {
        AddNotesAction.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    getLink() {
        return routing.generate(this.route, {id: this.model.get('id'), ...this.route_parameters});
    },

    _onAjaxSuccess(data) {
        this.model.set({notes: data.fields.notes});
        this._showAjaxSuccessMessage(data);
    },

    /**
     * @inheritdoc
     */
    _handleWidget() {
        if (this.dispatched) {
            return;
        }

        const notes = this.model.get('notes');
        const action = notes ? 'edit' : 'add';

        const shoppingListAddNotesModalView = new ShoppinglistAddNotesModalView({
            title: __(`oro.frontend.shoppinglist.lineitem.dialog.${action}.title`, {
                productName: _.escape(this.model.get('name'))
            }),
            okText: __(`oro.frontend.shoppinglist.lineitem.dialog.${action}.label`),
            cancelText: __('oro.frontend.shoppinglist.lineitem.dialog.cancel.label'),
            okCloses: false,
            notes
        });

        shoppingListAddNotesModalView.on('ok', () => {
            if (shoppingListAddNotesModalView.isValid()) {
                this.updateNotes(shoppingListAddNotesModalView.getValue());
                this._handleAjax();
                shoppingListAddNotesModalView.close();
            }
        });

        shoppingListAddNotesModalView.open();
    },

    updateNotes(notes) {
        this.model.set({
            notes,
            action_configuration: {
                ...(this.model.get('action_configuration') || {}),
                add_notes: !notes
            }
        });
    },

    /**
     * @inheritdoc
     */
    getActionParameters() {
        const params = AddNotesAction.__super__.getActionParameters.call(this);
        params.notes = this.model.get('notes');

        return JSON.stringify(params);
    },

    _showAjaxSuccessMessage() {
        mediator.execute(
            'showFlashMessage',
            'success',
            __('oro.frontend.shoppinglist.lineitem.dialog.notes.success')
        );
    }
});

export default AddNotesAction;
