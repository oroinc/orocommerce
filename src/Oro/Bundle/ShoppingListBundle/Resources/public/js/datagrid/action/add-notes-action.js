import __ from 'orotranslation/js/translator';
import routing from 'routing';
import mediator from 'oroui/js/mediator';
import ModelAction from 'oro/datagrid/action/model-action';
import Modal from 'oroui/js/modal';
import template from 'tpl-loader!oroshoppinglist/templates/actions/add-notes-action.html';

const ENTER_KEY_CODE = 13;

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

    validationRules: {
        notes: {
            Length: {
                max: 2048
            }
        }
    },

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

        const modal = new Modal({
            className: 'modal oro-modal-normal shopping-list-notes-modal',
            title: __(`oro.frontend.shoppinglist.lineitem.dialog.${action}.title`, {
                productName: this.model.get('name')
            }),
            okText: __(`oro.frontend.shoppinglist.lineitem.dialog.${action}.label`),
            cancelText: __('oro.frontend.shoppinglist.lineitem.dialog.cancel.label'),
            okCloses: false
        }).on('shown', () => {
            this.validator = modal.$('form').validate({
                rules: this.validationRules
            });
        });

        modal.setContent(template({
            arialLabelBy: modal.cid
        }));

        modal.on('ok', () => {
            if (this.validator.form()) {
                this.updateNotes(modal.$('[name="notes"]').val());
                this._handleAjax();
                modal.close();
            }
        });

        modal.open();

        modal.$('[name="notes"]')
            .focus()
            .val(notes)
            .on('keydown', e => {
                if (e.keyCode === ENTER_KEY_CODE && e.ctrlKey) {
                    modal.trigger('ok');
                }
            });
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
