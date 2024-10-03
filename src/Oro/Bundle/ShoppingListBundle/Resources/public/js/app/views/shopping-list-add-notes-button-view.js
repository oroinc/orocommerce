import BaseView from 'oroui/js/app/views/base/view';
import __ from 'orotranslation/js/translator';
import mediator from 'oroui/js/mediator';
import ShoppinglistAddNotesModalView from './shoppinglist-add-notes-modal-view';

const ShoppingListAddNotesButtonView = BaseView.extend({
    optionNames: BaseView.prototype.optionNames.concat(['shoppingList']),

    shoppingList: null,

    events: {
        click: 'onClick'
    },

    constructor: function ShoppingListAddNotesButtonView(...args) {
        ShoppingListAddNotesButtonView.__super__.constructor.apply(this, args);
    },

    onClick() {
        const shoppingListAddNotesModalView = new ShoppinglistAddNotesModalView({
            title: __(`oro.frontend.shoppinglist.dialog.notes.add`, {
                shoppingList: this.shoppingList.label
            }),
            okText: __(`oro.frontend.shoppinglist.dialog.notes.add_btn_label`),
            cancelText: __('Cancel'),
            okCloses: false
        });

        this.subview('shoppinglistAddNotesModalView', shoppingListAddNotesModalView);

        shoppingListAddNotesModalView.on('ok', () => {
            if (shoppingListAddNotesModalView.isValid()) {
                mediator.trigger(
                    `shopping-list-${this.shoppingList.id}-notes:update`,
                    shoppingListAddNotesModalView.getValue()
                );
                this.$el.addClass('hide');
                shoppingListAddNotesModalView.close();
            }
        });

        shoppingListAddNotesModalView.open();
    }
});

export default ShoppingListAddNotesButtonView;
