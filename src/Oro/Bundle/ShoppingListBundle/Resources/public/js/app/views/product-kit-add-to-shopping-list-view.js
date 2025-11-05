import ProductAddToShoppingListView from 'oroshoppinglist/js/app/views/product-add-to-shopping-list-view';
import routing from 'routing';
import mediator from 'oroui/js/mediator';
import $ from 'jquery';
import _ from 'underscore';
import __ from 'orotranslation/js/translator';

const ProductKitAddToShoppingListView = ProductAddToShoppingListView.extend({
    /**
     * @inheritdoc
     */
    constructor: function ProductKitAddToShoppingListView(options) {
        ProductKitAddToShoppingListView.__super__.constructor.call(this, options);
    },

    /**
     * @param {jQuery.Event} e
     */
    onClick: function(e) {
        e.preventDefault();

        if (!this.validateForm()) {
            return;
        }

        ProductKitAddToShoppingListView.__super__.onClick.call(this, e);
    },

    /**
     * Validates the form with extra fields that outside of it, returns true if it is valid, false otherwise
     * @returns {boolean}
     */
    validateForm: function() {
        const isValid = this.$form.validate().form();
        const extraIsValid = $(this.$form.data('extra-form-selector')).validate().form();
        return isValid && extraIsValid;
    },

    addWidgetUrlOptions() {
        if (!this.model) {
            return {};
        }
        return this.model.get('_widget_data') || {};
    },

    _collectAllButtons: function() {
        if (this.options.shoppingListAddToEnabled) {
            return ProductKitAddToShoppingListView.__super__._collectAllButtons.call(this);
        }

        let buttons = [];

        this.modelAttr.shopping_lists.forEach(function(shoppingList) {
            this._addShoppingListButtons(buttons, shoppingList);
        }, this);

        if (this.options.shoppingListCreateEnabled) {
            let $createNewButton = $(this.options.createNewButtonTemplate({id: null, label: ''}));
            $createNewButton = this.updateLabel($createNewButton, null);
            buttons.push($createNewButton);
        }

        if (buttons.length === 1) {
            const decoreClass = this.dropdownWidget.options.decoreClass || '';
            buttons = _.first(buttons).find(this.options.buttonsSelector).addClass(decoreClass);
        }

        return buttons;
    },

    _addProductToShoppingList(url, urlOptions, formData) {
        urlOptions = {...urlOptions, ...this.addWidgetUrlOptions()};
        ProductKitAddToShoppingListView.__super__._addProductToShoppingList.call(this, url, urlOptions, formData);
    },

    /**
     * @inheritdoc
     */
    _removeLineItem: function(url, urlOptions, formData) {
        const xhrPromise = this._removeProductFromShoppingList(url, {
            id: urlOptions.productId,
            onlyCurrent: 1,
            ...this.addWidgetUrlOptions()
        }, formData);

        const messageOptions = {namespace: 'shopping_list'};
        const flashMsg = __(this.options.messages.remove.success, {
            name: this.model.get('name')
        });
        xhrPromise.done(response => {
            mediator.execute('showFlashMessage', 'success', flashMsg, messageOptions);
        });
    },

    /**
     * @inheritdoc
     */
    _saveLineItem: function(url, urlOptions, formData) {
        if (this.model && !this.model.get('line_item_form_enable')) {
            return;
        }
        mediator.execute('showLoading');
        mediator.trigger('shopping-list:line-items:before-response', this.model);

        $.ajax({
            type: 'POST',
            url: routing.generate(
                'oro_shopping_list_frontend_product_kit_line_item_update',
                {
                    id: urlOptions.productId,
                    ...this.addWidgetUrlOptions()
                }
            ),
            data: formData,
            success: response => {
                mediator.trigger('shopping-list:line-items:update-response', this.model, response);
            },
            error: error => {
                mediator.trigger('shopping-list:line-items:error-response', this.model, error);
            },
            complete: () => {
                mediator.execute('hideLoading');
            }
        });
    },

    _onQuantityEnter() {
        // Nothing to do because a form is sent using standard approach. Otherwise, it will be sent twice.
    }
});

export default ProductKitAddToShoppingListView;
