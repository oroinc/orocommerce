import mediator from 'oroui/js/mediator';
const MassAction = require('oro/datagrid/action/mass-action');

/**
 * Save For Later mass action
 *
 * @export  oro/datagrid/action/save-for-later-mass-action
 * @class   oro.datagrid.action.SaveForLaterMassAction
 * @extends oro.datagrid.action.ModelAction
 */
const SaveForLaterMassAction = MassAction.extend({
    /**
     * @inheritdoc
     */
    constructor: function SaveForLaterMassAction(options) {
        SaveForLaterMassAction.__super__.constructor.call(this, options);
    },

    _onAjaxSuccess(data) {
        this.datagrid.hideLoading();
        this.datagrid.collection.fetch({reset: true, toggleLoading: false});
        this.datagrid.resetSelectionState();

        this._showAjaxSuccessMessage(data);
        mediator.trigger('products:saved-for-later', {gridName: this.datagrid.name, data: data});
    },

    /**
     * @inheritdoc
     */
    dispose: function() {
        this.datagrid.off(null, null, this);
        SaveForLaterMassAction.__super__.dispose.call(this);
    }
});

export default SaveForLaterMassAction;
