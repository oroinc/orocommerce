import $ from 'jquery';
import __ from 'orotranslation/js/translator';
import mediator from 'oroui/js/mediator';
import BaseView from 'oroui/js/app/views/base/view';
import viewportManager from 'oroui/js/viewport-manager';
import ShoppinglistNotesEditableView from 'oroshoppinglist/js/app/views/shoppinglist-notes-editable-view';
import popoverTemplate from 'tpl-loader!oroshoppinglist/templates/actions/edit-notes-popover.html';
import ShoppinglistItemNotesEditModel from '../models/shoppinglist-item-notes-edit-model';

const ShoppingListItemEditNotesView = BaseView.extend({
    optionNames: BaseView.prototype.optionNames.concat(['itemId']),

    /**
     * @inheritdoc
     */
    events: {
        'shown.bs.popover': 'onShow',
        'hidden.bs.popover': 'onHide',
        'click [data-role="notes"]': 'onClick'
    },

    autoRender: true,

    popoverConfig: {
        placement: 'bottom',
        html: true,
        sanitize: false
    },

    listen: {
        'viewport:mobile-big mediator': 'onViewportChange'
    },

    /**
     * @inheritdoc
     */
    constructor: function ShoppingListItemEditNotesView(options) {
        const {_initEvent: initEvent, ...restOptions} = options;
        this.rowModel = options.datagrid.collection.get(options.itemId);
        ShoppingListItemEditNotesView.__super__.constructor.call(this, restOptions);
    },

    /**
     * @inheritdoc
     */
    initialize(options) {
        this.popoverConfig = {...this.popoverConfig, ...options.popoverConfig};
        ShoppingListItemEditNotesView.__super__.initialize.call(this, options);

        this.onViewportChange();
    },

    initPopover() {
        this.$el.popover({
            ...this.popoverConfig,
            content: popoverTemplate({
                notes: this.getNotes()
            })
        });
    },

    onShow(popoverEvent) {
        const popover = $(popoverEvent.target).data('bs.popover');
        popover.tip.querySelector('[name="notes"]').value = this.getNotes();

        const shoppinglistItemNotesEditModel = new ShoppinglistItemNotesEditModel({
            notes: this.getNotes(),
            id: this.itemId
        });

        this.listenTo(shoppinglistItemNotesEditModel, 'change:notes', (model, notes) => {
            this.$('[data-role="notes"]').text(notes);
            this.$('.action').trigger('focus');
        });

        this.subview('shoppinglistNotesEditableView', new ShoppinglistNotesEditableView({
            model: shoppinglistItemNotesEditModel,
            el: popover.tip,
            onSuccess() {
                mediator.execute(
                    'showFlashMessage',
                    'success',
                    __('oro.frontend.shoppinglist.lineitem.dialog.notes.success')
                );

                popover.hide();
            },
            onSaveNote: notes => {
                this.rowModel.set('notes', notes);
            },
            onDecline: () => {
                popover.hide();
            }
        }));

        this.subview('shoppinglistNotesEditableView').$el.focusFirstInput();

        $(document).on(`click${this.eventNamespace()}`, event => {
            if (popover.tip && !popover.tip.contains(event.target)) {
                popover.hide();
            }
        });
    },

    onHide() {
        $(document).off(this.eventNamespace());
    },

    getNotes() {
        return this.$('[data-role="notes"]')[0].innerText;
    },

    dispose: function() {
        if (this.disposed) {
            return;
        }
        this.$el.popover('dispose');
        ShoppingListItemEditNotesView.__super__.dispose.call(this);
    },

    onViewportChange() {
        if (viewportManager.isApplicable('mobile-big')) {
            this.$el.popover('dispose');
        } else {
            this.initPopover();
        }
    },

    onClick(event) {
        if (viewportManager.isApplicable('mobile-big')) {
            event.preventDefault();
            this.$('.action').trigger('click');
        }
    }
});

export default ShoppingListItemEditNotesView;
