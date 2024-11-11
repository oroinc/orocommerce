import $ from 'jquery';
import mediator from 'oroui/js/mediator';
import routing from 'routing';
import __ from 'orotranslation/js/translator';
import BaseView from 'oroui/js/app/views/base/view';
import DialogWidget from 'oro/dialog-widget';

const AddAddressButtonView = BaseView.extend({
    optionNames: BaseView.prototype.optionNames.concat(
        ['entityId', 'entityClass', 'routeName', 'operations', 'fieldId', 'operationName']
    ),

    entityId: null,

    entityClass: null,

    routeName: 'oro_frontend_action_widget_form',

    operations: {},

    titles: {
        billing: __('oro.checkout.billing_address.label'),
        shipping: __('oro.checkout.shipping_address.label')
    },

    events: {
        click: 'showDialog'
    },

    constructor: function AddAddressButtonView(...args) {
        AddAddressButtonView.__super__.constructor.apply(this, args);
    },

    initialize(options) {
        this.$field = $(`#${this.fieldId}`);
        AddAddressButtonView.__super__.initialize.call(this, options);
    },

    showDialog() {
        const type = this.$field.data('address-type');
        const url = routing.generate(this.routeName, {
            operationName: this.getOperationName(type),
            entityClass: this.entityClass,
            entityId: this.entityId
        });

        this.subview('addNewAddressDialog', new DialogWidget({
            url,
            hasForm: true,
            title: this.getTitle(type),
            dialogOptions: {
                modal: true,
                width: 1000,
                height: 'auto',
                resizable: false,
                autoResize: true,
                dialogTitleIcon: 'map-pin'
            },
            fullscreenDialogOptions: {
                dialogTitleIcon: 'map-pin'
            }
        }));

        this.subview('addNewAddressDialog').on('formSave', () => {
            if (!this.$field.hasClass('custom-address')) {
                this.$field.addClass('custom-address');
            }

            this.$field.val(0);
            this.$field.trigger('forceChange');
            mediator.trigger('new-address-add');
            this.subview('addNewAddressDialog').hide();
        });
        this.subview('addNewAddressDialog').render();
    },

    /**
     * @param {string} type
     * @return string
     */
    getTitle: function(type) {
        if (this.titles.hasOwnProperty(type)) {
            return this.titles[type];
        }

        return this.titles.billing;
    },

    /**
     * @param {string} type
     * @return string
     */
    getOperationName: function(type) {
        if (this.operations.hasOwnProperty(type)) {
            return this.operations[type];
        }

        return this.operations.billing;
    }
});

export default AddAddressButtonView;
