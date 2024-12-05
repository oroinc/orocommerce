import Modal from 'oroui/js/modal';
import template from 'tpl-loader!oroshoppinglist/templates/actions/add-notes-action.html';

const ENTER_KEY_CODE = 13;

const ShoppinglistAddNotesModalView = Modal.extend({
    optionNames: Modal.prototype.optionNames.concat(['notes']),

    className: 'modal oro-modal-normal shopping-list-notes-modal',

    notes: '',

    validationRules: {
        notes: {
            Length: {
                max: 2048
            }
        }
    },

    events: {
        'keydown [name="notes"]': 'onTextareaKeydown'
    },

    constructor: function ShoppinglistAddNotesModalView(...args) {
        ShoppinglistAddNotesModalView.__super__.constructor.apply(this, args);
    },

    open(callback) {
        this.setContent(template({
            arialLabelBy: this.cid
        }));

        ShoppinglistAddNotesModalView.__super__.open.call(this, callback);

        this.$('[name="notes"]').trigger('focus').val(this.notes);

        this.validator = this.$('form').validate({
            rules: this.validationRules
        });

        return this;
    },

    onTextareaKeydown(event) {
        if (event.keyCode === ENTER_KEY_CODE && event.ctrlKey) {
            this.trigger('ok');
        }
    },

    isValid() {
        return this.validator?.form();
    },

    getValue() {
        return this.$('[name="notes"]').val();
    }
});

export default ShoppinglistAddNotesModalView;
