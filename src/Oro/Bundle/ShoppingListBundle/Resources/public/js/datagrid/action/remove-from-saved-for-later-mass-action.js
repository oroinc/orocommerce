import mediator from 'oroui/js/mediator';
import SaveForLaterMassAction from 'oro/datagrid/action/save-for-later-mass-action';

/**
 * Remove From Saved For Later mass action
 *
 * @export  oro/datagrid/action/remove-from-saved-for-later-mass-action
 * @class   oro.datagrid.action.RemoveFromSavedForLaterMassAction
 * @extends oro.datagrid.action.SaveForLaterMassAction
 */
const RemoveFromSavedForLaterMassAction = SaveForLaterMassAction.extend({
    /**
     * @inheritdoc
     */
    constructor: function RemoveFromSavedForLaterMassAction(options) {
        RemoveFromSavedForLaterMassAction.__super__.constructor.call(this, options);
    },

    _onAjaxSuccess(data) {
        this.datagrid.hideLoading();
        this.datagrid.collection.fetch({reset: true, toggleLoading: false});
        this.datagrid.resetSelectionState();

        this._showAjaxSuccessMessage(data);
        mediator.trigger('products:removed-from-saved-for-later', {gridName: this.datagrid.name, data: data});
    },

    /**
     * @inheritdoc
     */
    dispose: function() {
        this.datagrid.off(null, null, this);
        RemoveFromSavedForLaterMassAction.__super__.dispose.call(this);
    }
});

export default RemoveFromSavedForLaterMassAction;
