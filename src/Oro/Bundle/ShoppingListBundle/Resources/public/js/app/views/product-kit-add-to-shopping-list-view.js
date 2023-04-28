import ProductAddToShoppingListView from 'oroshoppinglist/js/app/views/product-add-to-shopping-list-view';
import routing from 'routing';
import mediator from 'oroui/js/mediator';
import $ from 'jquery';

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

        if (this.validateForm()) {
            return;
        }

        ProductKitAddToShoppingListView.__super__.onClick.call(this, e);
    },

    /**
     * Validates the form, returns true if it is valid, false otherwise
     * @returns {boolean}
     */
    validateForm: function() {
        return !this.$form.validate().form();
    },

    addWidgetUrlOptions() {
        if (!this.model) {
            return {};
        }
        return this.model.get('_widget_data') || {};
    },

    _addProductToShoppingList(url, urlOptions, formData) {
        urlOptions = {...urlOptions, ...this.addWidgetUrlOptions()};
        ProductKitAddToShoppingListView.__super__._addProductToShoppingList.call(this, url, urlOptions, formData);
    },

    /**
     * @inheritdoc
     */
    _removeLineItem: function(url, urlOptions, formData) {
        this._removeProductFromShoppingList(url, {
            id: urlOptions.productId,
            ...this.addWidgetUrlOptions()
        }, formData);
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
    }
});

export default ProductKitAddToShoppingListView;
